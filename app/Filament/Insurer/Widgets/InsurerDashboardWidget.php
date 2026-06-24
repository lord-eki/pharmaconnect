<?php

namespace App\Filament\Insurer\Widgets;

use App\Models\InsuranceClaim;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InsurerDashboardWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $providerId = auth()->user()->insuranceProvider->id ?? 0;

        // Pending claims requiring action
        $pendingClaims = InsuranceClaim::where('insurance_provider_id', $providerId)
            ->whereIn('status', ['submitted', 'under_review'])
            ->count();

        // This month's financial summary
        $thisMonthClaims = InsuranceClaim::where('insurance_provider_id', $providerId)
            ->whereMonth('submitted_at', now()->month)
            ->whereYear('submitted_at', now()->year);

        $totalClaimed = $thisMonthClaims->sum('claimed_amount');
        $totalApproved = (clone $thisMonthClaims)->where('status', 'approved')->sum('approved_amount');

        // Active orders
        $activeOrders = Order::whereHas('prescription.insuranceClaim', function ($query) use ($providerId) {
            $query->where('insurance_provider_id', $providerId);
        })
            ->whereIn('status', ['confirmed', 'processing', 'shipped'])
            ->count();

        // Approval rate
        $totalReviewed = (clone $thisMonthClaims)->whereIn('status', ['approved', 'rejected'])->count();
        $approvedCount = (clone $thisMonthClaims)->where('status', 'approved')->count();
        $approvalRate = $totalReviewed > 0 ? round(($approvedCount / $totalReviewed) * 100, 1) : 0;

        return [
            Stat::make('Pending Claims', $pendingClaims)
                ->description('Requires review')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->url(route('filament.Insurer.resources.insurance.claim-verifications.index', [
                    'tableFilters' => ['status' => ['values' => ['submitted', 'under_review']]],
                ])),

            Stat::make('This Month Claimed', 'KES '.number_format($totalClaimed, 2))
                ->description('Total submitted')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('info')
                ->chart([7, 4, 8, 12, 15, 10, 9]) 
                ->url(route('filament.Insurer.resources.insurance.insurer-reports.index')),

            Stat::make('This Month Approved', 'KES '.number_format($totalApproved, 2))
                ->description('Total approved')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->url(route('filament.Insurer.resources.insurance.insurer-reports.index')),

           

            Stat::make('Approval Rate', $approvalRate.'%')
                ->description('This month')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($approvalRate >= 80 ? 'success' : ($approvalRate >= 60 ? 'warning' : 'danger')),

            Stat::make('Price Catalogue', 'Available')
                ->description('View medicine prices')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('info')
                ->url(route('filament.Insurer.resources.insurance.pricing-catalogues.index')),

                Stat::make('Orders',$activeOrders)
                ->description('View Orders')
                ->descriptionIcon('heroicon-o-circle-stack')
                ->color('primary')
                ->url(route('filament.Insurer.resources.external-orders.index'))
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
