<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\Transaction;
use App\Models\InsuranceClaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Process all payments when order is delivered
     */
    public function processOrderPayments(Order $order): array
    {
        try {
            DB::beginTransaction();

            $results = [
                'payables' => [],
                'receivables' => [],
                'errors' => [],
            ];

            // 1. Create payable to supplier
            $payable = $this->createPayableToSupplier($order);
            if ($payable) {
                $results['payables'][] = $payable;
            }

            // 2. Create receivable from patient/insurance
            $receivable = $this->createReceivableForOrder($order);
            if ($receivable) {
                $results['receivables'][] = $receivable;
            }

            DB::commit();

            Log::info('Order payments processed', [
                'order_id' => $order->id,
                'payables' => count($results['payables']),
                'receivables' => count($results['receivables']),
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error processing order payments', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create payable to supplier 
     */

public function createPayableToSupplier(Order $order): ?Payable
{
    try {
        // Check if payable already exists
        $existing = Payable::where('order_id', $order->id)
            ->where('vendor_id', $order->supplier_id)
            ->first();

        if ($existing) {
            Log::info('Payable already exists for order', [
                'payable_id' => $existing->id,
                'order_id' => $order->id,
            ]);
            return $existing;
        }

        // Calculate amount owed to supplier (WITHOUT markup)
        // Use supplier_total if available, otherwise fall back to total_amount
        $supplierAmount = $order->supplier_total ?? $order->total_amount;

        $payable = Payable::create([
            'reference' => $this->generateReference('PAY'),
            'order_id' => $order->id,
            'vendor_id' => $order->supplier_id,
            'vendor_type' => 'supplier',
            'amount' => $supplierAmount, // Supplier gets ONLY their quoted price
            'payment_method' => 'bank_transfer', // Default
            'due_date' => now()->addDays(7), // Pay supplier within 7 days
        ]);

        // Create corresponding transaction
        $this->createTransaction($payable, 'payable', 'pending');

        Log::info('Payable created for supplier', [
            'payable_id' => $payable->id,
            'order_id' => $order->id,
            'supplier_id' => $order->supplier_id,
            'supplier_amount' => $supplierAmount,
            'markup_hidden' => $order->markup_total ?? 0,
            'total_with_markup' => $order->total_amount,
        ]);

        return $payable;

    } catch (\Exception $e) {
        Log::error('Error creating payable', [
            'order_id' => $order->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return null;
    }
}
   /**
 * Create receivable from patient/insurance
 */
public function createReceivableForOrder(Order $order): ?Receivable
{
    try {
        $prescription = $order->prescription;
        $patient = $prescription->patient;

        // Calculate amounts (using marked-up prices)
        $totalAmount = $order->total_amount; // This includes markup
        $deliveryFee = $order->delivery ? $order->delivery->delivery_fee : 0;
        $grandTotal = $totalAmount + $deliveryFee;

        // Check if insurance is involved
        $insuranceCovered = 0;
        $insuranceClaimId = null;
        $paymentSource = 'patient';

        if ($prescription->insurance_covered && $prescription->insuranceClaim) {
            $claim = $prescription->insuranceClaim;
            
            if ($claim->status === 'approved') {
                $insuranceCovered = $claim->approved_amount; // Based on marked-up price
                $insuranceClaimId = $claim->id;
                $paymentSource = 'insurance';
            }
        }

        // Patient portion (what patient owes)
        $patientPortion = $grandTotal - $insuranceCovered;

        // Create primary receivable (insurance or patient)
        $receivable = Receivable::create([
            'reference' => $this->generateReference('REC'),
            'order_id' => $order->id,
            'prescription_id' => $prescription->id,
            'patient_id' => $patient->id,
            'insurance_provider_id' => $insuranceClaimId ? $prescription->insuranceClaim->insurance_provider_id : null,
            'amount' => $insuranceCovered > 0 ? $insuranceCovered : $patientPortion, // Marked-up price
            'payment_source' => $paymentSource,
            'claim_status' => $insuranceClaimId ? 'submitted' : null,
            'claim_reference' => $insuranceClaimId ? $prescription->insuranceClaim->claim_number : null,
            'claim_submitted_at' => $insuranceClaimId ? now() : null,
        ]);

        // Create corresponding transaction
        $this->createTransaction($receivable, 'receivable', 'pending');

        Log::info('Receivable created for order', [
            'receivable_id' => $receivable->id,
            'order_id' => $order->id,
            'amount' => $receivable->amount,
            'payment_source' => $paymentSource,
            'includes_markup' => true,
            'markup_amount' => $order->markup_total ?? 0,
        ]);

        // If there's both insurance and patient portion, create separate receivable for patient
        if ($insuranceCovered > 0 && $patientPortion > 0) {
            $patientReceivable = Receivable::create([
                'reference' => $this->generateReference('REC'),
                'order_id' => $order->id,
                'prescription_id' => $prescription->id,
                'patient_id' => $patient->id,
                'amount' => $patientPortion, // Patient's share of marked-up price
                'payment_source' => 'patient',
            ]);

            $this->createTransaction($patientReceivable, 'receivable', 'pending');

            Log::info('Additional patient receivable created', [
                'receivable_id' => $patientReceivable->id,
                'order_id' => $order->id,
                'patient_portion' => $patientPortion,
            ]);
        }

        return $receivable;

    } catch (\Exception $e) {
        Log::error('Error creating receivable', [
            'order_id' => $order->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return null;
    }
}

    /**
     * Mark payable as paid
     */
    public function markPayableAsPaid(Payable $payable, array $paymentData): bool
    {
        try {
            DB::beginTransaction();

            $payable->update([
                'payment_method' => $paymentData['payment_method'] ?? $payable->payment_method,
                'gateway_reference' => $paymentData['gateway_reference'] ?? null,
                'paid_at' => now(),
            ]);

            // Update transaction status
            if ($payable->transaction) {
                $payable->transaction->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'notes' => $paymentData['notes'] ?? null,
                ]);
            }

            DB::commit();

            Log::info('Payable marked as paid', [
                'payable_id' => $payable->id,
                'amount' => $payable->amount,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error marking payable as paid', [
                'payable_id' => $payable->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Mark receivable as received
     */
    public function markReceivableAsReceived(Receivable $receivable, array $paymentData): bool
    {
        try {
            DB::beginTransaction();

            $receivable->update([
                'received_at' => now(),
            ]);

            // Update claim status if insurance payment
            if ($receivable->payment_source === 'insurance' && $receivable->claim_reference) {
                $receivable->update([
                    'claim_status' => 'paid',
                ]);

                // Update the actual insurance claim
                if ($receivable->prescription && $receivable->prescription->insuranceClaim) {
                    $receivable->prescription->insuranceClaim->update([
                        'status' => 'paid',
                    ]);
                }
            }

            // Update transaction status
            if ($receivable->transaction) {
                $receivable->transaction->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'notes' => $paymentData['notes'] ?? null,
                ]);
            }

            DB::commit();

            Log::info('Receivable marked as received', [
                'receivable_id' => $receivable->id,
                'amount' => $receivable->amount,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error marking receivable as received', [
                'receivable_id' => $receivable->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create transaction record for payable/receivable
     */
    protected function createTransaction($model, string $type, string $status): Transaction
    {
        return Transaction::create([
            'transactionable_type' => get_class($model),
            'transactionable_id' => $model->id,
            'reference' => $this->generateReference('TXN'),
            'amount' => $model->amount,
            'currency' => 'KES',
            'type' => $type,
            'status' => $status,
        ]);
    }

    /**
     * Generate unique reference number
     */
    protected function generateReference(string $prefix): string
    {
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(Str::random(8));
    }

    /**
     * Get payment summary for an order
     */
    public function getOrderPaymentSummary(Order $order): array
    {
        $payables = $order->payable ?? Payable::where('order_id', $order->id)->get();
        $receivables = Receivable::where('order_id', $order->id)->get();

        return [
            'order_total' => $order->total_amount,
            'delivery_fee' => $order->delivery ? $order->delivery->delivery_fee : 0,
            'grand_total' => $order->total_amount + ($order->delivery ? $order->delivery->delivery_fee : 0),
            'payables' => [
                'total' => $payables->sum('amount'),
                'paid' => $payables->whereNotNull('paid_at')->sum('amount'),
                'pending' => $payables->whereNull('paid_at')->sum('amount'),
                'items' => $payables->map(function ($payable) {
                    return [
                        'id' => $payable->id,
                        'reference' => $payable->reference,
                        'vendor' => $payable->vendor->name ?? 'N/A',
                        'amount' => $payable->amount,
                        'status' => $payable->paid_at ? 'paid' : 'pending',
                        'paid_at' => $payable->paid_at?->toIso8601String(),
                    ];
                }),
            ],
            'receivables' => [
                'total' => $receivables->sum('amount'),
                'received' => $receivables->whereNotNull('received_at')->sum('amount'),
                'pending' => $receivables->whereNull('received_at')->sum('amount'),
                'items' => $receivables->map(function ($receivable) {
                    return [
                        'id' => $receivable->id,
                        'reference' => $receivable->reference,
                        'payment_source' => $receivable->payment_source,
                        'amount' => $receivable->amount,
                        'status' => $receivable->received_at ? 'received' : 'pending',
                        'received_at' => $receivable->received_at?->toIso8601String(),
                    ];
                }),
            ],
        ];
    }

    /**
     * Get outstanding payables 
     */
    public function getOutstandingPayables(): array
    {
        $payables = Payable::whereNull('paid_at')
            ->with(['vendor', 'order'])
            ->get();

        return [
            'total_amount' => $payables->sum('amount'),
            'total_count' => $payables->count(),
            'overdue_amount' => $payables->where('due_date', '<', now())->sum('amount'),
            'overdue_count' => $payables->where('due_date', '<', now())->count(),
            'by_vendor' => $payables->groupBy('vendor_id')->map(function ($group) {
                return [
                    'vendor_name' => $group->first()->vendor->name ?? 'N/A',
                    'total_amount' => $group->sum('amount'),
                    'count' => $group->count(),
                ];
            })->values(),
        ];
    }

    /**
     * Get outstanding receivables 
     */
    public function getOutstandingReceivables(): array
    {
        $receivables = Receivable::whereNull('received_at')
            ->with(['patient', 'insuranceProvider'])
            ->get();

        return [
            'total_amount' => $receivables->sum('amount'),
            'total_count' => $receivables->count(),
            'by_source' => [
                'patient' => $receivables->where('payment_source', 'patient')->sum('amount'),
                'insurance' => $receivables->where('payment_source', 'insurance')->sum('amount'),
            ],
            'insurance_pending' => $receivables->where('payment_source', 'insurance')
                ->whereIn('claim_status', ['submitted', 'under_review', 'approved'])
                ->sum('amount'),
        ];
    }
}