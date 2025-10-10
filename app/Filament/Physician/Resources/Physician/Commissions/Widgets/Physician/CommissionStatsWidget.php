<?php

namespace App\Filament\Physician\Resources\Physician\Commissions\Widgets\Physician;

use App\Models\Commission;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CommissionStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $physicianId = Auth::id();
        
        // Pending commissions
        $pendingAmount = Commission::where('physician_id', $physicianId)
            ->where('status', 'pending')
            ->sum('commission_amount');
        
        $pendingCount = Commission::where('physician_id', $physicianId)
            ->where('status', 'pending')
            ->count();
        
        // Approved but not paid
        $approvedAmount = Commission::where('physician_id', $physicianId)
            ->where('status', 'approved')
            ->sum('commission_amount');
        
        $approvedCount = Commission::where('physician_id', $physicianId)
            ->where('status', 'approved')
            ->count();
        
        // This month's earnings
        $thisMonthEarnings = Commission::monthlyEarnings($physicianId);
        
        // Last month's earnings for comparison
        $lastMonthEarnings = Commission::monthlyEarnings(
            $physicianId, 
            now()->subMonth()->year, 
            now()->subMonth()->month
        );
        
        $monthDifference = $thisMonthEarnings - $lastMonthEarnings;
        $monthPercentage = $lastMonthEarnings > 0 
            ? round(($monthDifference / $lastMonthEarnings) * 100, 1) 
            : 0;
        
        // Total lifetime earnings
        $totalEarnings = Commission::where('physician_id', $physicianId)
            ->where('status', 'paid')
            ->sum('commission_amount');
        
        // Average commission per order
        $avgCommission = Commission::where('physician_id', $physicianId)
            ->where('status', 'paid')
            ->avg('commission_amount');

        return [
            Stat::make('Pending Commission', 'KES ' . number_format($pendingAmount, 2))
                ->description($pendingCount . ' orders pending approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->chart([100, 150, 200, 180, 220, 250, $pendingAmount]),
            
            Stat::make('Approved (Unpaid)', 'KES ' . number_format($approvedAmount, 2))
                ->description($approvedCount . ' orders awaiting payment')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info'),
            
            Stat::make('This Month Earnings', 'KES ' . number_format($thisMonthEarnings, 2))
                ->description(
                    ($monthPercentage > 0 ? '+' : '') . 
                    $monthPercentage . '% from last month'
                )
                ->descriptionIcon($monthDifference >= 0 
                    ? 'heroicon-m-arrow-trending-up' 
                    : 'heroicon-m-arrow-trending-down')
                ->color($monthDifference >= 0 ? 'success' : 'danger')
                ->chart([
                    $lastMonthEarnings * 0.7,
                    $lastMonthEarnings * 0.8,
                    $lastMonthEarnings * 0.9,
                    $lastMonthEarnings,
                    $thisMonthEarnings * 0.7,
                    $thisMonthEarnings * 0.9,
                    $thisMonthEarnings
                ]),
            
            Stat::make('Total Earned', 'KES ' . number_format($totalEarnings, 2))
                ->description('Lifetime earnings')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),
            
            Stat::make('Average Per Order', 'KES ' . number_format($avgCommission ?? 0, 2))
                ->description('Average commission amount')
                ->descriptionIcon('heroicon-o-calculator')
                ->color('gray'),
        ];
    }
}
