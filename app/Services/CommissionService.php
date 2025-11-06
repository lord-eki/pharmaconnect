<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Order;
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
            
            // Calculate amounts
            $grossAmount = $order->total_amount;
            $commissionAmount = $grossAmount * ($commissionRate / 100);

            // Create commission record
            $commission = Commission::create([
                'physician_id' => $physician->id,
                'prescription_id' => $order->prescription_id,
                'order_id' => $order->id,
                'commission_rate' => $commissionRate,
                'gross_amount' => $grossAmount,
                'commission_amount' => $commissionAmount,
                'status' => 'pending',
            ]);

            Log::info('Commission calculated and created', [
                'commission_id' => $commission->id,
                'order_id' => $order->id,
                'physician_id' => $physician->id,
                'commission_amount' => $commissionAmount,
            ]);

            return $commission;

        } catch (\Exception $e) {
            Log::error('Error calculating commission', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get commission rate for physician
     * Can be dynamic based on various factors
     */
    protected function getCommissionRate(User $physician, Order $order): float
    {
        // Default rate
        $defaultRate = 10.0; // 10%

        // Check for custom rate in system settings or physician profile
        if (isset($physician->profile->preferences['commission_rate'])) {
            return (float) $physician->profile->preferences['commission_rate'];
        }

        // Tiered rates based on monthly volume
        $monthlyVolume = $this->getMonthlyVolume($physician);

        if ($monthlyVolume >= 1000000) { // KES 1M+
            return 15.0; // 15% for high performers
        } elseif ($monthlyVolume >= 500000) { // KES 500K+
            return 12.5; // 12.5%
        } elseif ($monthlyVolume >= 100000) { // KES 100K+
            return 11.0; // 11%
        }

        return $defaultRate;
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
     */
    public function approveCommission(Commission $commission, User $approver): bool
    {
        try {
            $commission->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approver->id,
            ]);

            Log::info('Commission approved', [
                'commission_id' => $commission->id,
                'approved_by' => $approver->id,
            ]);

            return true;

        } catch (\Exception $e) {
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
            if ($commission->status !== 'approved') {
                throw new \Exception('Commission must be approved before marking as paid');
            }

            $commission->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference' => $paymentReference,
            ]);

            Log::info('Commission marked as paid', [
                'commission_id' => $commission->id,
                'payment_reference' => $paymentReference,
            ]);

            return true;

        } catch (\Exception $e) {
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

        return [
            'total_commissions' => $commissions->count(),
            'total_gross_amount' => $commissions->sum('gross_amount'),
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

                if (!$commission) {
                    $results['failed']++;
                    $results['errors'][] = "Commission {$commissionId} not found";
                    continue;
                }

                if ($this->approveCommission($commission, $approver)) {
                    $results['approved']++;
                } else {
                    $results['failed']++;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error in batch commission approval', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $results;
    }
}