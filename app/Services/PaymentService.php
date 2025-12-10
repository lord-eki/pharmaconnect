<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Patient;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\Supplier;
use App\Models\Transaction;
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
    protected function createPayableToSupplier(Order $order): ?Payable
    {
        try {
            $payable = Payable::create([
                'reference' => $this->generatePayableReference(),
                'order_id' => $order->id,
                'vendor_id' => $order->supplier->user_id,
                'vendor_type' => 'supplier',
                'amount' => $order->supplier_total,
                'due_date' => now()->addDays(30),
            ]);

            // Then create transaction with the payable ID
            $this->createTransaction($payable, 'payable', $order->status);

            Log::info('Payable created successfully', [
                'payable_id' => $payable->id,
                'order_id' => $order->id,
                'amount' => $order->supplier_total,
            ]);

            return $payable;

        } catch (\Exception $e) {
            Log::error('Error creating payable', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function createReceivableForOrder(Order $order): ?Receivable
    {
        try {
            $prescription = $order->prescription;
            $patient = $prescription->patient;

            // Create receivable record FIRST
            $receivable = Receivable::create([
                'reference' => $this->generateReceivableReference(),
                'order_id' => $order->id,
                'prescription_id' => $prescription->id,
                'patient_id' => $patient->id,
                'insurance_provider_id' => $patient->insurance_provider_id,
                'amount' => $order->total_amount,
                'payment_source' => $prescription->insurance_covered ? 'mixed' : 'patient',
            ]);

            // Then create transaction with the receivable ID
            $this->createTransaction($receivable, 'receivable', $order->status);

            Log::info('Receivable created successfully', [
                'receivable_id' => $receivable->id,
                'order_id' => $order->id,
                'amount' => $order->total_amount,
            ]);

            return $receivable;

        } catch (\Exception $e) {
            Log::error('Error creating receivable', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
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
        return $prefix.'-'.date('Ymd').'-'.strtoupper(Str::random(8));
    }

    protected function generatePayableReference(): string
    {
        return 'PAY-'.date('Ymd').'-'.strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }

    protected function generateReceivableReference(): string
    {
        return 'REC-'.date('Ymd').'-'.strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }

    /**
     * Get payment summary for an order
     */
    public function getOrderPaymentSummary(Order $order): array
    {
        $payables = Payable::where('order_id', $order->id)->get();
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
