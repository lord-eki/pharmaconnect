<?php

namespace App\Filament\Supplier\Resources\Supplier\Financials\Widgets\Supplier;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FinancialStatsWidget extends StatsOverviewWidget
{
  protected function getStats(): array
    {
        $supplierId = Auth::user()->supplier->user_id;

        if (!$supplierId) {
            return [];
        }

        // This month earnings
        $thisMonthEarnings = Payment::where('payee_id', $supplierId)
            ->where('status', 'completed')
            ->whereMonth('processed_at', now()->month)
            ->whereYear('processed_at', now()->year)
            ->sum('amount');

        $lastMonthEarnings = Payment::where('payee_id', $supplierId)
            ->where('status', 'completed')
            ->whereMonth('processed_at', now()->subMonth()->month)
            ->whereYear('processed_at', now()->subMonth()->year)
            ->sum('amount');

        $earningsChange = $lastMonthEarnings > 0 
            ? (($thisMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100 
            : 0;

        // Pending payments
        $pendingAmount = Payment::where('payee_id', $supplierId)
            ->where('status', 'pending')
            ->sum('amount');

        // Total lifetime earnings
        $totalEarnings = Payment::where('payee_id', $supplierId)
            ->where('status', 'completed')
            ->sum('amount');

        return [
            Stat::make('This Month Earnings', 'KES ' . number_format($thisMonthEarnings, 2))
                ->description(($earningsChange >= 0 ? '+' : '') . number_format($earningsChange, 1) . '% from last month')
                ->descriptionIcon($earningsChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($earningsChange >= 0 ? 'success' : 'danger'),

            Stat::make('Pending Payments', 'KES ' . number_format($pendingAmount, 2))
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total  Earnings', 'KES ' . number_format($totalEarnings, 2))
                ->description('All completed payments')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
