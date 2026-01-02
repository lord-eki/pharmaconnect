<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Receivable;
use App\Models\Payable;
use Illuminate\Support\Facades\DB;

class RevenueService
{
    /**
     * Get comprehensive revenue metrics for a date range
     */
    public function getRevenueMetrics($startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfDay();
        $endDate = $endDate ?? now()->endOfDay();

        // 1. GROSS REVENUE (Total Receivables from delivered orders)
        $grossRevenue = $this->getGrossRevenue($startDate, $endDate);

        // 2. COLLECTED REVENUE (Actually received payments)
        $collectedRevenue = $this->getCollectedRevenue($startDate, $endDate);

        // 3. PENDING REVENUE (Delivered but not yet paid)
        $pendingRevenue = $this->getPendingRevenue($startDate, $endDate);

        // 4. COST OF GOODS SOLD (Supplier payables)
        $cogs = $this->getCostOfGoodsSold($startDate, $endDate);

        // 5. COMMISSION EXPENSES (Physician commissions)
        $commissionExpense = $this->getCommissionExpense($startDate, $endDate);

        // 6. GROSS PROFIT (Revenue - COGS)
        $grossProfit = $grossRevenue - $cogs;

        // 7. NET PROFIT (Gross Profit - Commissions - Delivery Costs)
        $deliveryCosts = $this->getDeliveryCosts($startDate, $endDate);
        $netProfit = $grossProfit - $commissionExpense;

        // 8. PROFIT MARGINS
        $grossMargin = $grossRevenue > 0 ? ($grossProfit / $grossRevenue) * 100 : 0;
        $netMargin = $grossRevenue > 0 ? ($netProfit / $grossRevenue) * 100 : 0;

        return [
            'gross_revenue' => $grossRevenue,
            'collected_revenue' => $collectedRevenue,
            'pending_revenue' => $pendingRevenue,
            'cost_of_goods_sold' => $cogs,
            'commission_expense' => $commissionExpense,
            'delivery_costs' => $deliveryCosts,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'gross_margin_percent' => round($grossMargin, 2),
            'net_margin_percent' => round($netMargin, 2),
            'collection_rate' => $grossRevenue > 0 ? round(($collectedRevenue / $grossRevenue) * 100, 2) : 0,
        ];
    }

    /**
     * Get gross revenue (total receivables from delivered orders)
     * This is the total amount customers owe us
     */
    public function getGrossRevenue($startDate, $endDate): float
    {
        return Receivable::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->where('status', 'delivered')
                  ->whereBetween('delivered_at', [$startDate, $endDate]);
        })->sum('amount');
    }

    /**
     * Get collected revenue (actually received payments)
     * This is cash that has actually come in
     */
    public function getCollectedRevenue($startDate, $endDate): float
    {
        return Receivable::whereNotNull('received_at')
            ->whereBetween('received_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get pending revenue (delivered but not yet collected)
     */
    public function getPendingRevenue($startDate, $endDate): float
    {
        return Receivable::whereNull('received_at')
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'delivered')
                      ->whereBetween('delivered_at', [$startDate, $endDate]);
            })->sum('amount');
    }

    /**
     * Get Cost of Goods Sold (supplier payables)
     */
    public function getCostOfGoodsSold($startDate, $endDate): float
    {
        return Payable::where('vendor_type', 'supplier')
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'delivered')
                      ->whereBetween('delivered_at', [$startDate, $endDate]);
            })->sum('amount');
    }

    /**
     * Get commission expenses
     */
    public function getCommissionExpense($startDate, $endDate): float
    {
        return Payable::where('vendor_type', 'physician')
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'delivered')
                      ->whereBetween('delivered_at', [$startDate, $endDate]);
            })->sum('amount');
    }

    /**
     * Get delivery costs (from delivery fees)
     */
    public function getDeliveryCosts($startDate, $endDate): float
    {
        return Order::where('status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->whereHas('delivery')
            ->with('delivery')
            ->get()
            ->sum(function ($order) {
                return $order->delivery->delivery_fee ?? 0;
            });
    }

    /**
     * Get revenue breakdown by payment source
     */
    public function getRevenueBySource($startDate, $endDate): array
    {
        $receivables = Receivable::whereHas('order', function ($query) use ($startDate, $endDate) {
            $query->where('status', 'delivered')
                  ->whereBetween('delivered_at', [$startDate, $endDate]);
        })->get();

        return [
            'patient' => [
                'total' => $receivables->where('payment_source', 'patient')->sum('amount'),
                'collected' => $receivables->where('payment_source', 'patient')
                    ->whereNotNull('received_at')->sum('amount'),
                'pending' => $receivables->where('payment_source', 'patient')
                    ->whereNull('received_at')->sum('amount'),
                'count' => $receivables->where('payment_source', 'patient')->count(),
            ],
            'insurance' => [
                'total' => $receivables->where('payment_source', 'insurance')->sum('amount'),
                'collected' => $receivables->where('payment_source', 'insurance')
                    ->whereNotNull('received_at')->sum('amount'),
                'pending' => $receivables->where('payment_source', 'insurance')
                    ->whereNull('received_at')->sum('amount'),
                'count' => $receivables->where('payment_source', 'insurance')->count(),
            ],
        ];
    }

    /**
     * Get revenue by supplier
     */
    public function getRevenueBySupplier($startDate, $endDate): array
    {
        return Order::where('status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->with(['supplier', 'receivables', 'payables'])
            ->get()
            ->groupBy('supplier_id')
            ->map(function ($orders, $supplierId) {
                $totalRevenue = $orders->sum(function ($order) {
                    return $order->receivables->sum('amount');
                });

                $supplierCost = $orders->sum(function ($order) {
                    return $order->payables->where('vendor_type', 'supplier')->sum('amount');
                });

                $commissions = $orders->sum(function ($order) {
                    return $order->payables->where('vendor_type', 'physician')->sum('amount');
                });

                return [
                    'supplier_name' => $orders->first()->supplier->name ?? 'Unknown',
                    'order_count' => $orders->count(),
                    'total_revenue' => $totalRevenue,
                    'supplier_cost' => $supplierCost,
                    'commission_expense' => $commissions,
                    'gross_profit' => $totalRevenue - $supplierCost,
                    'net_profit' => $totalRevenue - $supplierCost - $commissions,
                ];
            })
            ->values()
            ->sortByDesc('total_revenue')
            ->values();
    }

    /**
     * Get aging report for outstanding receivables
     */
    public function getReceivablesAgingReport(): array
    {
        $receivables = Receivable::whereNull('received_at')
            ->with(['order', 'patient'])
            ->get();

        $now = now();

        return [
            'current' => [
                'amount' => $receivables->filter(function ($r) use ($now) {
                    return $r->order && $r->order->delivered_at && 
                           $r->order->delivered_at->diffInDays($now) <= 30;
                })->sum('amount'),
                'count' => $receivables->filter(function ($r) use ($now) {
                    return $r->order && $r->order->delivered_at && 
                           $r->order->delivered_at->diffInDays($now) <= 30;
                })->count(),
            ],
            '31_60_days' => [
                'amount' => $receivables->filter(function ($r) use ($now) {
                    if (!$r->order || !$r->order->delivered_at) return false;
                    $days = $r->order->delivered_at->diffInDays($now);
                    return $days > 30 && $days <= 60;
                })->sum('amount'),
                'count' => $receivables->filter(function ($r) use ($now) {
                    if (!$r->order || !$r->order->delivered_at) return false;
                    $days = $r->order->delivered_at->diffInDays($now);
                    return $days > 30 && $days <= 60;
                })->count(),
            ],
            '61_90_days' => [
                'amount' => $receivables->filter(function ($r) use ($now) {
                    if (!$r->order || !$r->order->delivered_at) return false;
                    $days = $r->order->delivered_at->diffInDays($now);
                    return $days > 60 && $days <= 90;
                })->sum('amount'),
                'count' => $receivables->filter(function ($r) use ($now) {
                    if (!$r->order || !$r->order->delivered_at) return false;
                    $days = $r->order->delivered_at->diffInDays($now);
                    return $days > 60 && $days <= 90;
                })->count(),
            ],
            'over_90_days' => [
                'amount' => $receivables->filter(function ($r) use ($now) {
                    return $r->order && $r->order->delivered_at && 
                           $r->order->delivered_at->diffInDays($now) > 90;
                })->sum('amount'),
                'count' => $receivables->filter(function ($r) use ($now) {
                    return $r->order && $r->order->delivered_at && 
                           $r->order->delivered_at->diffInDays($now) > 90;
                })->count(),
            ],
        ];
    }

    /**
     * Get daily revenue trend
     */
    public function getDailyRevenueTrend($days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        return Order::where('status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, now()])
            ->selectRaw('DATE(delivered_at) as date')
            ->with(['receivables'])
            ->get()
            ->groupBy('date')
            ->map(function ($orders) {
                return $orders->sum(function ($order) {
                    return $order->receivables->sum('amount');
                });
            })
            ->toArray();
    }

    /**
     * Get comprehensive financial summary
     */
    public function getFinancialSummary($startDate = null, $endDate = null): array
    {
        $metrics = $this->getRevenueMetrics($startDate, $endDate);
        $bySource = $this->getRevenueBySource($startDate, $endDate);
        $aging = $this->getReceivablesAgingReport();

        return [
            'period' => [
                'start' => $startDate ?? now()->startOfDay(),
                'end' => $endDate ?? now()->endOfDay(),
            ],
            'revenue' => $metrics,
            'by_source' => $bySource,
            'aging' => $aging,
            'outstanding_payables' => $this->getOutstandingPayables(),
        ];
    }

    /**
     * Get outstanding payables summary
     */
    private function getOutstandingPayables(): array
    {
        $payables = Payable::whereNull('paid_at')->get();

        return [
            'total' => $payables->sum('amount'),
            'overdue' => $payables->where('due_date', '<', now())->sum('amount'),
            'supplier' => $payables->where('vendor_type', 'supplier')->sum('amount'),
            'commission' => $payables->where('vendor_type', 'physician')->sum('amount'),
        ];
    }
}