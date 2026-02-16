<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\SupplierMedicine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderReassignmentService
{
    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Handle supplier rejection of an order
     */
    public function rejectOrder(Order $order, string $reason, ?int $rejectedBy = null): bool
    {
        return DB::transaction(function () use ($order, $reason, $rejectedBy) {
            // Store original supplier if this is first rejection
            if (!$order->original_supplier_id) {
                $order->original_supplier_id = $order->supplier_id;
            }

            // Add to rejection history
            $history = $order->reassignment_history ?? [];
            $history[] = [
                'rejected_at' => now()->toIso8601String(),
                'rejected_supplier_id' => $order->supplier_id,
                'rejected_supplier_name' => $order->supplier->company_name,
                'reason' => $reason,
                'rejected_by' => $rejectedBy,
            ];

            // Update order
            $order->update([
                'is_rejected' => true,
                'rejection_reason' => $reason,
                'rejected_at' => now(),
                'rejected_by' => $rejectedBy,
                'status' => 'pending_reassignment',
                'reassignment_history' => $history,
            ]);

            // Restore stock if it was already deducted
            if (in_array($order->status, ['confirmed', 'processing'])) {
                $this->restoreStockAfterRejection($order);
            }

            Log::info('Order rejected by supplier', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'supplier_id' => $order->supplier_id,
                'reason' => $reason,
                'reassignment_count' => count($history),
            ]);

            return true;
        });
    }

    /**
     * Find next best supplier for an order
     */
    public function findNextBestSupplier(Order $order): ?array
    {
        $prescription = $order->prescription;
        $externalOrder = $order->externalOrder;
        
        if (!$prescription && !$externalOrder) {
            Log::error('Order has no prescription or external order', [
                'order_id' => $order->id,
            ]);
            return null;
        }

        // Get items from the order - must eager load medicine relationship
        $orderItems = $order->items()->with('medicine')->get();
        
        // Get list of suppliers already rejected
        $rejectedSupplierIds = collect($order->reassignment_history ?? [])
            ->pluck('rejected_supplier_id')
            ->push($order->supplier_id)
            ->unique()
            ->values()
            ->toArray();

        Log::info('Finding next supplier', [
            'order_id' => $order->id,
            'rejected_suppliers' => $rejectedSupplierIds,
            'item_count' => $orderItems->count(),
        ]);

        // Find suppliers who can fulfill ALL items
        $availableSuppliers = $this->findSuppliersForItems($orderItems, $rejectedSupplierIds);

        if (empty($availableSuppliers)) {
            Log::warning('No alternative suppliers found', [
                'order_id' => $order->id,
                'rejected_count' => count($rejectedSupplierIds),
            ]);
            return null;
        }

        // Sort by total cost (cheapest first)
        usort($availableSuppliers, function ($a, $b) {
            return $a['total_cost'] <=> $b['total_cost'];
        });

        return $availableSuppliers[0] ?? null;
    }

    /**
     * Reassign order to a new supplier (manual reassignment by operations)
     */
    public function reassignToSupplier(Order $order, int $newSupplierId, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($order, $newSupplierId, $notes) {
            $newSupplier = Supplier::findOrFail($newSupplierId);

            // Verify supplier can fulfill all items
            $canFulfill = $this->verifySupplierCanFulfill($order, $newSupplierId);
            
            if (!$canFulfill) {
                throw new \Exception("Supplier {$newSupplier->company_name} cannot fulfill all order items");
            }

            // Get old supplier name before update
            $oldSupplierName = $order->supplier->company_name;
            
            // Recalculate pricing for new supplier
            $pricing = $this->recalculatePricingForSupplier($order, $newSupplierId);

            // Update order
            $oldSupplierId = $order->supplier_id;
            $order->update([
                'supplier_id' => $newSupplierId,
                'supplier_total' => $pricing['supplier_total'],
                'markup_total' => $pricing['markup_total'],
                'total_amount' => $pricing['total_amount'],
                'status' => 'sent_to_supplier',
                'sent_to_supplier_at' => now(),
                'is_rejected' => false,
                'rejection_reason' => null,
                'reassignment_count' => $order->reassignment_count + 1,
                'notes' => ($order->notes ?? '') . "\n\n" . 
                          "Reassigned from {$oldSupplierName} to {$newSupplier->company_name}" .
                          ($notes ? ": {$notes}" : ''),
            ]);

            // Update order items with new pricing
            foreach ($order->items as $item) {
                $newPrice = $pricing['items'][$item->medicine_id] ?? null;
                if ($newPrice) {
                    $item->update([
                        'unit_price' => $newPrice['unit_price'],
                        'total_price' => $newPrice['total_price'],
                    ]);
                }
            }

            Log::info('Order reassigned to new supplier', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'old_supplier_id' => $oldSupplierId,
                'new_supplier_id' => $newSupplierId,
                'new_total' => $pricing['total_amount'],
                'reassignment_count' => $order->reassignment_count,
            ]);

            // Notify new supplier directly (instead of calling notifyStakeholders)
            if ($newSupplier->user) {
                try {
                    $newSupplier->user->notify(new \App\Notifications\NewOrderNotification($order));
                    Log::info('New supplier notified of reassigned order', [
                        'order_id' => $order->id,
                        'supplier_id' => $newSupplierId,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to notify new supplier', [
                        'order_id' => $order->id,
                        'supplier_id' => $newSupplierId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return true;
        });
    }

    /**
     * Auto-reassign to next cheapest supplier
     */
    public function autoReassignToNextSupplier(Order $order): bool
    {
        $nextSupplier = $this->findNextBestSupplier($order);

        if (!$nextSupplier) {
            Log::warning('No alternative supplier available for auto-reassignment', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            
            // Mark order as needing manual intervention
            $order->update([
                'status' => 'needs_manual_assignment',
                'notes' => ($order->notes ?? '') . "\n\n" . 
                          "Auto-reassignment failed: No alternative suppliers available",
            ]);
            
            return false;
        }

        try {
            $this->reassignToSupplier(
                $order, 
                $nextSupplier['supplier_id'],
                "Auto-assigned to next cheapest supplier after rejection"
            );

            Log::info('Order auto-reassigned successfully', [
                'order_id' => $order->id,
                'new_supplier_id' => $nextSupplier['supplier_id'],
                'new_total' => $nextSupplier['total_cost'],
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Auto-reassignment failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $order->update([
                'status' => 'needs_manual_assignment',
                'notes' => ($order->notes ?? '') . "\n\n" . 
                          "Auto-reassignment failed: {$e->getMessage()}",
            ]);

            return false;
        }
    }

    /**
     * Find suppliers who can fulfill all items
     */
    protected function findSuppliersForItems($orderItems, array $excludeSupplierIds = []): array
    {
        $medicineIds = $orderItems->pluck('medicine_id')->toArray();
        
        // Get all supplier medicines for these items
        $supplierMedicines = SupplierMedicine::whereIn('medicine_id', $medicineIds)
            ->whereNotIn('supplier_id', $excludeSupplierIds)
            ->where('is_available', true)
            ->where('stock_quantity', '>', 0)
            ->with(['supplier', 'medicine']) // Eager load both supplier and medicine
            ->get()
            ->groupBy('supplier_id');

        $viableSuppliers = [];

        foreach ($supplierMedicines as $supplierId => $medicines) {
            // Check if supplier can fulfill ALL items
            $canFulfillAll = true;
            $totalCost = 0;
            $supplierItems = [];

            foreach ($orderItems as $orderItem) {
                $supplierMedicine = $medicines->where('medicine_id', $orderItem->medicine_id)->first();
                
                if (!$supplierMedicine || $supplierMedicine->stock_quantity < $orderItem->quantity) {
                    $canFulfillAll = false;
                    break;
                }

                // Calculate cost with markup - pass Medicine object
                $priceData = $this->pricingService->calculateFinalPrice(
                    $supplierMedicine->unit_price,
                    $orderItem->medicine, // Pass the Medicine model
                    $orderItem->quantity
                );

                $totalCost += $priceData['final_total'];
                $supplierItems[$orderItem->medicine_id] = [
                    'unit_price' => $priceData['final_unit_price'],
                    'total_price' => $priceData['final_total'],
                    'supplier_price' => $priceData['supplier_price'],
                    'markup_amount' => $priceData['markup_amount'],
                    'stock_available' => $supplierMedicine->stock_quantity,
                ];
            }

            if ($canFulfillAll) {
                $supplier = $medicines->first()->supplier;
                
                $viableSuppliers[] = [
                    'supplier_id' => $supplierId,
                    'supplier_name' => $supplier->company_name,
                    'total_cost' => $totalCost,
                    'items' => $supplierItems,
                ];
            }
        }

        return $viableSuppliers;
    }

    /**
     * Verify supplier can fulfill order
     */
    protected function verifySupplierCanFulfill(Order $order, int $supplierId): bool
    {
        foreach ($order->items as $item) {
            $supplierMedicine = SupplierMedicine::where('supplier_id', $supplierId)
                ->where('medicine_id', $item->medicine_id)
                ->where('is_available', true)
                ->first();

            if (!$supplierMedicine || $supplierMedicine->stock_quantity < $item->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recalculate pricing for new supplier
     */
    protected function recalculatePricingForSupplier(Order $order, int $supplierId): array
    {
        $supplierTotal = 0;
        $markupTotal = 0;
        $totalAmount = 0;
        $items = [];

        // Eager load medicine relationship
        $orderItems = $order->items()->with('medicine')->get();

        foreach ($orderItems as $item) {
            $supplierMedicine = SupplierMedicine::where('supplier_id', $supplierId)
                ->where('medicine_id', $item->medicine_id)
                ->firstOrFail();

            // Pass Medicine object to calculateFinalPrice
            $priceData = $this->pricingService->calculateFinalPrice(
                $supplierMedicine->unit_price,
                $item->medicine, // Pass the Medicine model
                $item->quantity
            );

            $supplierTotal += $priceData['supplier_total'];
            $markupTotal += $priceData['markup_amount'] * $item->quantity;
            $totalAmount += $priceData['final_total'];

            $items[$item->medicine_id] = [
                'unit_price' => $priceData['final_unit_price'],
                'total_price' => $priceData['final_total'],
                'supplier_price' => $priceData['supplier_price'],
                'markup_amount' => $priceData['markup_amount'],
            ];
        }

        return [
            'supplier_total' => $supplierTotal,
            'markup_total' => $markupTotal,
            'total_amount' => $totalAmount,
            'items' => $items,
        ];
    }

    /**
     * Restore stock after rejection
     */
    protected function restoreStockAfterRejection(Order $order): void
    {
        foreach ($order->items as $item) {
            $supplierMedicine = SupplierMedicine::where('supplier_id', $order->supplier_id)
                ->where('medicine_id', $item->medicine_id)
                ->first();

            if ($supplierMedicine) {
                $supplierMedicine->increment('stock_quantity', $item->quantity);
                $supplierMedicine->update(['last_updated' => now()]);

                Log::info('Stock restored after order rejection', [
                    'order_id' => $order->id,
                    'medicine_id' => $item->medicine_id,
                    'quantity_restored' => $item->quantity,
                ]);
            }
        }
    }

    /**
     * Get reassignment options for an order
     */
    public function getReassignmentOptions(Order $order): array
    {
        $nextBest = $this->findNextBestSupplier($order);
        
        // Get order items with medicine relationship
        $orderItems = $order->items()->with('medicine')->get();
        
        $allOptions = $this->findSuppliersForItems(
            $orderItems, 
            collect($order->reassignment_history ?? [])->pluck('rejected_supplier_id')->toArray()
        );

        return [
            'recommended' => $nextBest,
            'all_options' => $allOptions,
            'current_supplier' => [
                'id' => $order->supplier_id,
                'name' => $order->supplier->company_name,
                'total' => $order->supplier_total,
            ],
            'rejection_count' => $order->reassignment_count,
            'can_auto_reassign' => !empty($nextBest),
        ];
    }
}