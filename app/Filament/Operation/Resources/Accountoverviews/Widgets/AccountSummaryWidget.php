<?php

namespace App\Filament\Operation\Resources\Accountoverviews\Widgets;

use App\Models\Payable;
use App\Models\Receivable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountSummaryWidget extends StatsOverviewWidget
{
     protected function getStats(): array
    {
        // Get filtered data from the table
        $tableFilters = $this->getTableFilters();
        
        // Calculate totals
        $totalReceived = $this->getFilteredReceivables($tableFilters, true)->sum('amount');
        $totalPending = $this->getFilteredReceivables($tableFilters, false)->sum('amount');
        $totalPaid = $this->getFilteredPayables($tableFilters, true)->sum('amount');
        $totalUnpaid = $this->getFilteredPayables($tableFilters, false)->sum('amount');
        
        $netBalance = $totalReceived - $totalPaid;
        $pendingBalance = $totalPending - $totalUnpaid;

        return [
            Stat::make('Total Money Received', 'KES ' . number_format($totalReceived, 2))
                ->description('Completed receivables')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->chart($this->getReceivablesChart()),

            Stat::make('Total Money Paid Out', 'KES ' . number_format($totalPaid, 2))
                ->description('Completed payables')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger')
                ->chart($this->getPayablesChart()),

            Stat::make('Net Balance', 'KES ' . number_format($netBalance, 2))
                ->description($netBalance >= 0 ? 'Positive cash flow' : 'Negative cash flow')
                ->descriptionIcon($netBalance >= 0 ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle')
                ->color($netBalance >= 0 ? 'success' : 'danger'),

            Stat::make('Pending Receivables', 'KES ' . number_format($totalPending, 2))
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Unpaid Payables', 'KES ' . number_format($totalUnpaid, 2))
                ->description('Amount due')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning'),

            Stat::make('Projected Balance', 'KES ' . number_format($netBalance + $pendingBalance, 2))
                ->description('Including pending transactions')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),
        ];
    }

    protected function getTableFilters(): array
    {
        // This will be populated by the table component
        return $this->tableFilters ?? [];
    }

    protected function getFilteredReceivables($filters, $completed = null)
    {
        $query = Receivable::query();

        if (isset($filters['type']) && $filters['type'] === 'payable') {
            return $query->whereRaw('1 = 0'); // Return empty if filtering for payables only
        }

        if (isset($filters['category'])) {
            if (in_array($filters['category'], ['patient', 'insurance'])) {
                $query->where('payment_source', $filters['category']);
            }
        }

        if ($completed === true) {
            $query->whereNotNull('received_at');
        } elseif ($completed === false) {
            $query->whereNull('received_at');
        }

        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (isset($filters['until'])) {
            $query->whereDate('created_at', '<=', $filters['until']);
        }

        return $query;
    }

    protected function getFilteredPayables($filters, $completed = null)
    {
        $query = Payable::query();

        if (isset($filters['type']) && $filters['type'] === 'receivable') {
            return $query->whereRaw('1 = 0'); // Return empty if filtering for receivables only
        }

        if (isset($filters['category'])) {
            if (in_array($filters['category'], ['supplier', 'physician'])) {
                $query->where('vendor_type', $filters['category']);
            }
        }

        if ($completed === true) {
            $query->whereNotNull('paid_at');
        } elseif ($completed === false) {
            $query->whereNull('paid_at');
        }

        if (isset($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (isset($filters['until'])) {
            $query->whereDate('created_at', '<=', $filters['until']);
        }

        return $query;
    }

    protected function getReceivablesChart(): array
    {
        // Last 7 days of receivables
        $data = Receivable::query()
            ->whereNotNull('received_at')
            ->where('received_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(received_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->toArray();

        return array_pad($data, 7, 0);
    }

    protected function getPayablesChart(): array
    {
        // Last 7 days of payables
        $data = Payable::query()
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->toArray();

        return array_pad($data, 7, 0);
    }
}
