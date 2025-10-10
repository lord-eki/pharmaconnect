<?php

namespace App\Filament\Physician\Widgets\Physician;

use App\Models\Commission;
use App\Models\Prescription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PhysicianStatsOverview extends StatsOverviewWidget
{

protected function getStats(): array
    {
        $physicianId = Auth::id();
        
        // Get current month statistics
        $currentMonth = now()->startOfMonth();
        
        $totalPrescriptions = Prescription::where('physician_id', $physicianId)
            ->where('created_at', '>=', $currentMonth)
            ->count();
            
        $fulfilledPrescriptions = Prescription::where('physician_id', $physicianId)
            ->where('status', 'fulfilled')
            ->where('fulfilled_at', '>=', $currentMonth)
            ->count();
            
        $pendingPrescriptions = Prescription::where('physician_id', $physicianId)
            ->whereIn('status', ['submitted', 'processing'])
            ->count();
            
        $monthlyCommission = Commission::where('physician_id', $physicianId)
            ->where('created_at', '>=', $currentMonth)
            ->where('status', 'approved')
            ->sum('commission_amount');
            
        $pendingCommission = Commission::where('physician_id', $physicianId)
            ->where('status', 'pending')
            ->sum('commission_amount');

        return [
            Stat::make('Total Prescriptions (This Month)', $totalPrescriptions)
                ->description('Prescriptions created this month')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
                
            Stat::make('Fulfilled Prescriptions', $fulfilledPrescriptions)
                ->description('Successfully completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Pending Orders', $pendingPrescriptions)
                ->description('Awaiting fulfillment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Monthly Commission', 'KES ' . number_format($monthlyCommission, 2))
                ->description('Earned this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
                
            Stat::make('Pending Commission', 'KES ' . number_format($pendingCommission, 2))
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
                
        ];
    }
}