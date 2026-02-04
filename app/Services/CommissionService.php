<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Order;
use App\Models\Payable;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    /**
     * Calculate and create commission for a delivered order
     */
    public function calculateCommissionForOrder(Order $order): ?Commission
    {
        try {
            // Only calculate for delivered orders
            if ($order->status !== 'delivered') {
                Log::info('Order not delivered, skipping commission calculation', [
                    'order_id' => $order->id,
                    'status' => $order->status,
                ]);
                return null;
            }

            // Check if commission already exists
            if ($order->commission) {
                Log::info('Commission already exists for order', [
                    'order_id' => $order->id,
                    'commission_id' => $order->commission->id,
                ]);

                return $order->commission;
            }

            $physician = $order->prescription->physician;

            // Get commission rate
            $commissionRate = $this->getCommissionRate($physician, $order);

            // Calculate commission on markup amount 
            $markupAmount = $order->markup_total; 
            $commissionAmount = $markupAmount * ($commissionRate / 100);

            // Don't create commission if amount is zero or negative
            if ($commissionAmount <= 0) {
                Log::warning('Commission amount is zero or negative, skipping creation', [
                    'order_id' => $order->id,
                    'markup_amount' => $markupAmount,
                    'commission_rate' => $commissionRate,
                ]);
                return null;
            }

            // Create commission record 
            $commission = Commission::create([
                'physician_id' => $physician->id,
                'prescription_id' => $order->prescription_id,
                'order_id' => $order->id,
                'commission_rate' => $commissionRate,
                'gross_amount' => $markupAmount, 
                'commission_amount' => $commissionAmount,
                'status' => 'pending',
            ]);

            // Link to the payable created by PaymentService
            $commissionPayable = Payable::where('order_id', $order->id)
                ->where('vendor_id', $physician->id)
                ->where('vendor_type', 'physician')
                ->first();

            if ($commissionPayable) {
                Log::info('Commission linked to payable', [
                    'commission_id' => $commission->id,
                    'payable_id' => $commissionPayable->id,
                ]);
            }

            Log::info('Commission calculated and created', [
                'commission_id' => $commission->id,
                'order_id' => $order->id,
                'physician_id' => $physician->id,
                'markup_amount' => $markupAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'calculation' => "{$markupAmount} × {$commissionRate}% = {$commissionAmount}",
            ]);

            return $commission;

        } catch (\Exception $e) {
            Log::error('Error calculating commission', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get commission rate for physician
     */
    protected function getCommissionRate(User $physician, Order $order): float
    {
        $phys = $physician->physician;

        if (! $phys) {
            Log::warning('Physician profile not found for commission calculation', [
                'order_id' => $order->id,
                'user_id' => $physician->id,
                'prescription_id' => $order->prescription_id,
            ]);

            return 5.00;
        }

        $rate = $phys->commission_rate ?? 5.00;

        Log::info('Commission rate retrieved for physician', [
            'physician_id' => $phys->id,
            'commission_rate' => $rate,
            'order_id' => $order->id,
        ]);

        return $rate;
    }

    /**
     * Get physician's monthly prescription volume
     */
    protected function getMonthlyVolume(User $physician): float
    {
        return Order::whereHas('prescription', function ($query) use ($physician) {
            $query->where('physician_id', $physician->id);
        })
            ->where('status', 'delivered')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');
    }

    /**
     * Approve commission for payment
     * This updates both the Commission record and the Payable
     */
    public function approveCommission(Commission $commission, User $approver): bool
    {
        try {
            DB::beginTransaction();

            // Update commission record
            $commission->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approver->id,
            ]);

            // Find and update the related payable
            $payable = Payable::where('order_id', $commission->order_id)
                ->where('vendor_id', $commission->physician_id)
                ->where('vendor_type', 'physician')
                ->first();

            if ($payable && !$payable->paid_at) {
                Log::info('Commission payable ready for payment', [
                    'payable_id' => $payable->id,
                    'commission_id' => $commission->id,
                ]);
            }

            DB::commit();

            Log::info('Commission approved', [
                'commission_id' => $commission->id,
                'approved_by' => $approver->id,
                'amount' => $commission->commission_amount,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error approving commission', [
                'commission_id' => $commission->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(Commission $commission, string $paymentReference): bool
    {
        try {
            DB::beginTransaction();

            if ($commission->status !== 'approved') {
                throw new \Exception('Commission must be approved before marking as paid');
            }

            // Update commission record
            $commission->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference' => $paymentReference,
            ]);

            // Update the related payable
            $payable = Payable::where('order_id', $commission->order_id)
                ->where('vendor_id', $commission->physician_id)
                ->where('vendor_type', 'physician')
                ->first();

            if ($payable && !$payable->paid_at) {
                $payable->update([
                    'paid_at' => now(),
                    'payment_method' => 'bank_transfer',
                    'gateway_reference' => $paymentReference,
                ]);

                Log::info('Commission payable marked as paid', [
                    'payable_id' => $payable->id,
                    'payment_reference' => $paymentReference,
                ]);
            }

            DB::commit();

            Log::info('Commission marked as paid', [
                'commission_id' => $commission->id,
                'payment_reference' => $paymentReference,
                'amount' => $commission->commission_amount,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error marking commission as paid', [
                'commission_id' => $commission->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get physician commission summary
     */
    public function getPhysicianCommissionSummary(User $physician, $startDate = null, $endDate = null): array
    {
        $query = Commission::where('physician_id', $physician->id);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $commissions = $query->get();

        // Also get payables for more accurate payment tracking
        $payablesQuery = Payable::where('vendor_id', $physician->id)
            ->where('vendor_type', 'physician');

        if ($startDate) {
            $payablesQuery->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $payablesQuery->where('created_at', '<=', $endDate);
        }

        $payables = $payablesQuery->get();

        return [
            'total_commissions' => $commissions->count(),
            'total_markup_amount' => $commissions->sum('gross_amount'), // Now represents markup
            'total_commission_amount' => $commissions->sum('commission_amount'),
            'pending_amount' => $commissions->where('status', 'pending')->sum('commission_amount'),
            'approved_amount' => $commissions->where('status', 'approved')->sum('commission_amount'),
            'paid_amount' => $commissions->where('status', 'paid')->sum('commission_amount'),
            'average_commission_rate' => $commissions->avg('commission_rate'),
            'by_status' => [
                'pending' => $commissions->where('status', 'pending')->count(),
                'approved' => $commissions->where('status', 'approved')->count(),
                'paid' => $commissions->where('status', 'paid')->count(),
            ],
            'payables_summary' => [
                'total' => $payables->sum('amount'),
                'paid' => $payables->whereNotNull('paid_at')->sum('amount'),
                'pending' => $payables->whereNull('paid_at')->sum('amount'),
            ],
            'average_commission' => $commissions->avg('commission_amount'),
            'highest_commission' => $commissions->max('commission_amount'),
            'total_orders' => $commissions->count(),
        ];
    }

    /**
     * Batch approve commissions
     */
    public function batchApproveCommissions(array $commissionIds, User $approver): array
    {
        $results = [
            'approved' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($commissionIds as $commissionId) {
                $commission = Commission::find($commissionId);

                if (! $commission) {
                    $results['failed']++;
                    $results['errors'][] = "Commission {$commissionId} not found";
                    continue;
                }

                if ($this->approveCommission($commission, $approver)) {
                    $results['approved']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to approve commission {$commissionId}";
                }
            }

            DB::commit();

            Log::info('Batch commission approval completed', [
                'approved' => $results['approved'],
                'failed' => $results['failed'],
                'approver_id' => $approver->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in batch commission approval', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $results;
    }

    /**
     * Get commission breakdown by order
     */
    public function getOrderCommissionBreakdown(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'supplier_cost' => $order->supplier_total,
            'markup_amount' => $order->markup_total,
            'total_amount' => $order->total_amount,
            'commission' => $order->commission ? [
                'id' => $order->commission->id,
                'rate' => $order->commission->commission_rate,
                'amount' => $order->commission->commission_amount,
                'status' => $order->commission->status,
                'calculation' => "{$order->markup_total} × {$order->commission->commission_rate}% = {$order->commission->commission_amount}",
            ] : null,
            'net_profit' => $order->markup_total - ($order->commission?->commission_amount ?? 0),
            'profit_breakdown' => [
                'gross_markup' => $order->markup_total,
                'physician_commission' => $order->commission?->commission_amount ?? 0,
                'net_to_platform' => $order->markup_total - ($order->commission?->commission_amount ?? 0),
            ],
        ];
    }

    /**
     * Get platform profit summary
     */
    public function getPlatformProfitSummary($startDate = null, $endDate = null): array
    {
        $query = Order::where('status', 'delivered');

        if ($startDate) {
            $query->whereDate('delivered_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('delivered_at', '<=', $endDate);
        }

        $orders = $query->with('commission')->get();

        $totalMarkup = $orders->sum('markup_total');
        $totalCommissions = $orders->sum(function ($order) {
            return $order->commission?->commission_amount ?? 0;
        });
        $netProfit = $totalMarkup - $totalCommissions;

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'total_supplier_cost' => $orders->sum('supplier_total'),
            'total_markup' => $totalMarkup,
            'total_commissions_paid' => $totalCommissions,
            'net_platform_profit' => $netProfit,
            'average_markup_per_order' => $orders->avg('markup_total'),
            'average_commission_per_order' => $orders->avg(function ($order) {
                return $order->commission?->commission_amount ?? 0;
            }),
            'profit_margin' => $orders->sum('total_amount') > 0 
                ? ($netProfit / $orders->sum('total_amount')) * 100 
                : 0,
        ];
    }
}