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
                'receivable' => null,
                'payables' => [
                    'supplier' => null,
                    'commission' => null,
                ],
                'errors' => [],
            ];

            // 1. Create receivable from patient/insurance
            $receivable = $this->createReceivableForOrder($order);
            if ($receivable) {
                $results['receivable'] = $receivable;
            }

            // 2. Create payable to supplier
            $supplierPayable = $this->createPayableToSupplier($order);
            if ($supplierPayable) {
                $results['payables']['supplier'] = $supplierPayable;
            }

            // 3. Create payable for physician commission
            $commissionPayable = $this->createCommissionPayable($order);
            if ($commissionPayable) {
                $results['payables']['commission'] = $commissionPayable;
            }

            DB::commit();

            Log::info('Order payments processed', [
                'order_id' => $order->id,
                'receivable_amount' => $receivable?->amount,
                'supplier_payable_amount' => $supplierPayable?->amount,
                'commission_payable_amount' => $commissionPayable?->amount,
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error processing order payments', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create receivable from patient/insurance
     * 
     */
    protected function createReceivableForOrder(Order $order): ?Receivable
{
    try {
        // Eager load necessary relationships to avoid null values
        $order->load(['prescription.patient.insuranceProvider']);
        
        $prescription = $order->prescription;
        
        if (!$prescription) {
            Log::error('Cannot create receivable - order has no prescription', [
                'order_id' => $order->id,
            ]);
            return null;
        }
        
        $patient = $prescription->patient;
        
        if (!$patient) {
            Log::error('Cannot create receivable - prescription has no patient', [
                'order_id' => $order->id,
                'prescription_id' => $prescription->id,
            ]);
            return null;
        }

        $paymentSource = 'patient'; 

        // Determine if insurance will pay
        // Check BOTH prescription flag AND patient has complete insurance info
        if ($prescription->insurance_covered && 
            $patient->insurance_provider_id && 
            $patient->insurance_number) {
            $paymentSource = 'insurance';
            
            Log::info('Receivable will be from insurance', [
                'order_id' => $order->id,
                'patient_id' => $patient->id,
                'insurance_provider_id' => $patient->insurance_provider_id,
                'insurance_number' => $patient->insurance_number,
            ]);
        } else {
            Log::info('Receivable will be from patient', [
                'order_id' => $order->id,
                'patient_id' => $patient->id,
                'prescription_insurance_covered' => $prescription->insurance_covered,
                'patient_has_provider' => !empty($patient->insurance_provider_id),
                'patient_has_number' => !empty($patient->insurance_number),
            ]);
        }

        // Total amount includes order total + delivery fee
        $totalAmount = $order->total_amount;
        if ($order->delivery) {
            $totalAmount += $order->delivery->delivery_fee;
        }

        // Create receivable record
        $receivable = Receivable::create([
            'reference' => $this->generateReceivableReference(),
            'order_id' => $order->id,
            'prescription_id' => $prescription->id,
            'patient_id' => $patient->id,
            'insurance_provider_id' => $paymentSource === 'insurance' ? $patient->insurance_provider_id : null,
            'amount' => $totalAmount,
            'payment_source' => $paymentSource,
        ]);

        // Create transaction for tracking
        $this->createTransaction($receivable, 'receivable', 'pending');

        Log::info('Receivable created successfully', [
            'receivable_id' => $receivable->id,
            'order_id' => $order->id,
            'amount' => $totalAmount,
            'payment_source' => $paymentSource,
            'insurance_provider_id' => $receivable->insurance_provider_id,
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
     * Create payable to supplier
     * 
     */
    protected function createPayableToSupplier(Order $order): ?Payable
    {
        try {
            // Supplier gets the supplier_total 
            $supplierAmount = $order->supplier_total;

            $payable = Payable::create([
                'reference' => $this->generatePayableReference(),
                'order_id' => $order->id,
                'vendor_id' => $order->supplier->user_id,
                'vendor_type' => 'supplier',
                'amount' => $supplierAmount,
                'due_date' => now()->addDays(30), 
                'description' => "Payment to supplier for order {$order->order_number}",
            ]);

            // Create transaction for tracking
            $this->createTransaction($payable, 'payable', 'pending');

            Log::info('Supplier payable created successfully', [
                'payable_id' => $payable->id,
                'order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'amount' => $supplierAmount,
            ]);

            return $payable;

        } catch (\Exception $e) {
            Log::error('Error creating supplier payable', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create payable for physician commission
     * 
     */
    protected function createCommissionPayable(Order $order): ?Payable
    {
        try {
            $prescription = $order->prescription;
            $physician = $prescription->physician;

            // Get physician commission rate
            $commissionRate = $this->getPhysicianCommissionRate($physician, $order);

            // Calculate commission on MARKUP amount
            $markupAmount = $order->markup_total; // This is the profit margin
            $commissionAmount = $markupAmount * ($commissionRate / 100);

            // Don't create payable if commission is zero or negative
            if ($commissionAmount <= 0) {
                Log::info('Commission amount is zero or negative, skipping payable creation', [
                    'order_id' => $order->id,
                    'markup_amount' => $markupAmount,
                    'commission_rate' => $commissionRate,
                ]);
                return null;
            }

            $payable = Payable::create([
                'reference' => $this->generatePayableReference(),
                'order_id' => $order->id,
                'vendor_id' => $physician->id,
                'vendor_type' => 'physician',
                'amount' => $commissionAmount,
                'due_date' => now()->addDays(15), // Pay physicians faster (Net 15)
                'description' => "Commission for Dr. {$physician->name} - Order {$order->order_number}",
                'metadata' => [
                    'commission_rate' => $commissionRate,
                    'markup_amount' => $markupAmount,
                    'calculation' => "{$markupAmount} × {$commissionRate}% = {$commissionAmount}",
                ],
            ]);

            // Create transaction for tracking
            $this->createTransaction($payable, 'payable', 'pending');

            Log::info('Commission payable created successfully', [
                'payable_id' => $payable->id,
                'order_id' => $order->id,
                'physician_id' => $physician->id,
                'markup_amount' => $markupAmount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
            ]);

            return $payable;

        } catch (\Exception $e) {
            Log::error('Error creating commission payable', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get physician commission rate
     */
    protected function getPhysicianCommissionRate($physician, Order $order): float
    {
        // Try to get rate from physician profile
        if ($physician->physician && $physician->physician->commission_rate) {
            return $physician->physician->commission_rate;
        }

        // Default rate
        $defaultRate = 5.00; // 5% of markup

        Log::info('Using default commission rate', [
            'physician_id' => $physician->id,
            'order_id' => $order->id,
            'rate' => $defaultRate,
        ]);

        return $defaultRate;
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
                'vendor_type' => $payable->vendor_type,
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
                'payment_method' => $paymentData['payment_method'] ?? null,
                'gateway_reference' => $paymentData['gateway_reference'] ?? null,
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
                        'paid_at' => now(),
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
                'payment_source' => $receivable->payment_source,
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
        $receivables = Receivable::where('order_id', $order->id)->get();
        $payables = Payable::where('order_id', $order->id)->get();

        // Separate payables by type
        $supplierPayables = $payables->where('vendor_type', 'supplier');
        $commissionPayables = $payables->where('vendor_type', 'physician');

        return [
            'order_breakdown' => [
                'supplier_total' => $order->supplier_total,
                'markup_total' => $order->markup_total,
                'order_total' => $order->total_amount,
                'delivery_fee' => $order->delivery ? $order->delivery->delivery_fee : 0,
                'grand_total' => $order->total_amount + ($order->delivery ? $order->delivery->delivery_fee : 0),
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
            'payables' => [
                'total' => $payables->sum('amount'),
                'paid' => $payables->whereNotNull('paid_at')->sum('amount'),
                'pending' => $payables->whereNull('paid_at')->sum('amount'),
                'supplier' => [
                    'total' => $supplierPayables->sum('amount'),
                    'paid' => $supplierPayables->whereNotNull('paid_at')->sum('amount'),
                    'pending' => $supplierPayables->whereNull('paid_at')->sum('amount'),
                    'items' => $supplierPayables->map(function ($payable) {
                        return [
                            'id' => $payable->id,
                            'reference' => $payable->reference,
                            'vendor' => $payable->vendor->name ?? 'N/A',
                            'amount' => $payable->amount,
                            'status' => $payable->paid_at ? 'paid' : 'pending',
                            'paid_at' => $payable->paid_at?->toIso8601String(),
                            'due_date' => $payable->due_date?->toIso8601String(),
                        ];
                    }),
                ],
                'commission' => [
                    'total' => $commissionPayables->sum('amount'),
                    'paid' => $commissionPayables->whereNotNull('paid_at')->sum('amount'),
                    'pending' => $commissionPayables->whereNull('paid_at')->sum('amount'),
                    'items' => $commissionPayables->map(function ($payable) {
                        return [
                            'id' => $payable->id,
                            'reference' => $payable->reference,
                            'physician' => $payable->vendor->name ?? 'N/A',
                            'amount' => $payable->amount,
                            'commission_rate' => $payable->metadata['commission_rate'] ?? null,
                            'markup_amount' => $payable->metadata['markup_amount'] ?? null,
                            'status' => $payable->paid_at ? 'paid' : 'pending',
                            'paid_at' => $payable->paid_at?->toIso8601String(),
                            'due_date' => $payable->due_date?->toIso8601String(),
                        ];
                    }),
                ],
            ],
            'profit_analysis' => [
                'gross_profit' => $order->markup_total,
                'commission_expense' => $commissionPayables->sum('amount'),
                'net_profit' => $order->markup_total - $commissionPayables->sum('amount'),
            ],
        ];
    }

    /**
     * Get outstanding payables grouped by type
     */
    public function getOutstandingPayables(): array
    {
        $payables = Payable::whereNull('paid_at')
            ->with(['vendor', 'order'])
            ->get();

        $supplierPayables = $payables->where('vendor_type', 'supplier');
        $commissionPayables = $payables->where('vendor_type', 'physician');

        return [
            'total_amount' => $payables->sum('amount'),
            'total_count' => $payables->count(),
            'overdue_amount' => $payables->where('due_date', '<', now())->sum('amount'),
            'overdue_count' => $payables->where('due_date', '<', now())->count(),
            'by_type' => [
                'supplier' => [
                    'amount' => $supplierPayables->sum('amount'),
                    'count' => $supplierPayables->count(),
                    'overdue_amount' => $supplierPayables->where('due_date', '<', now())->sum('amount'),
                    'overdue_count' => $supplierPayables->where('due_date', '<', now())->count(),
                ],
                'commission' => [
                    'amount' => $commissionPayables->sum('amount'),
                    'count' => $commissionPayables->count(),
                    'overdue_amount' => $commissionPayables->where('due_date', '<', now())->sum('amount'),
                    'overdue_count' => $commissionPayables->where('due_date', '<', now())->count(),
                ],
            ],
            'by_vendor' => $payables->groupBy('vendor_id')->map(function ($group) {
                return [
                    'vendor_name' => $group->first()->vendor->name ?? 'N/A',
                    'vendor_type' => $group->first()->vendor_type,
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
                'patient' => [
                    'amount' => $receivables->where('payment_source', 'patient')->sum('amount'),
                    'count' => $receivables->where('payment_source', 'patient')->count(),
                ],
                'insurance' => [
                    'amount' => $receivables->where('payment_source', 'insurance')->sum('amount'),
                    'count' => $receivables->where('payment_source', 'insurance')->count(),
                ],
            ],
            'insurance_pending' => $receivables->where('payment_source', 'insurance')
                ->whereIn('claim_status', ['submitted', 'under_review', 'approved'])
                ->sum('amount'),
        ];
    }

    /**
     * Get physician commission earnings
     */
    public function getPhysicianCommissionEarnings($physicianId, $startDate = null, $endDate = null): array
    {
        $query = Payable::where('vendor_id', $physicianId)
            ->where('vendor_type', 'physician');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $payables = $query->get();

        return [
            'total_earned' => $payables->sum('amount'),
            'paid' => $payables->whereNotNull('paid_at')->sum('amount'),
            'pending' => $payables->whereNull('paid_at')->sum('amount'),
            'count' => $payables->count(),
            'average_commission' => $payables->avg('amount'),
            'orders' => $payables->pluck('order_id')->unique()->count(),
        ];
    }
}