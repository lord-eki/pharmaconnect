<?php

namespace App\Filament\Operation\Widgets;

use App\Models\Delivery;
use App\Models\InsuranceClaim;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Receivable;
use App\Models\Payable;
use App\Models\Rider;
use App\Services\RevenueService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenueService = app(RevenueService::class);
        
        // Today's metrics
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        // Get proper revenue metrics for today
        $todayMetrics = $revenueService->getRevenueMetrics($today, $todayEnd);
        
        // Yesterday's revenue for comparison
        $yesterday = now()->subDay()->startOfDay();
        $yesterdayEnd = now()->subDay()->endOfDay();
        $yesterdayMetrics = $revenueService->getRevenueMetrics($yesterday, $yesterdayEnd);

        // Calculate change
        $revenueChange = $yesterdayMetrics['gross_revenue'] > 0
            ? (($todayMetrics['gross_revenue'] - $yesterdayMetrics['gross_revenue']) / $yesterdayMetrics['gross_revenue']) * 100
            : 0;

        // Orders metrics
        $pendingOrders = Order::where('status', 'pending_review')->count();
        $sentToSupplier = Order::where('status', 'sent_to_supplier')->count();
        $activeOrders = Order::whereIn('status', ['confirmed', 'processing', 'shipped'])->count();
        $deliveredToday = Order::where('status', 'delivered')
            ->whereDate('delivered_at', $today)
            ->count();

        // Delivery metrics
        $pendingDeliveries = Delivery::where('status', 'pending')->count();
        $inTransitDeliveries = Delivery::whereIn('status', ['assigned', 'picked_up', 'in_transit'])->count();

        // Prescription metrics
        $pendingPrescriptions = Prescription::where('status', 'pending')->count();
        $quotedPrescriptions = Prescription::where('status', 'quoted')->count();

        // Insurance claims
        $pendingClaims = InsuranceClaim::whereIn('status', ['submitted', 'under_review'])->count();

        // Active riders
        $availableRiders = Rider::where('is_active', true)
            ->where('is_available', true)
            ->count();

        $totalActiveRiders = Rider::where('is_active', true)->count();

        // Outstanding financials
        $outstandingReceivables = Receivable::whereNull('received_at')->sum('amount');
        $outstandingPayables = Payable::whereNull('paid_at')->sum('amount');
        $overduePayables = Payable::whereNull('paid_at')
            ->where('due_date', '<', now())
            ->sum('amount');

        return [
            // REVENUE METRICS
            // Stat::make('Today\'s Gross Revenue', 'KES '.number_format($todayMetrics['gross_revenue'], 2))
            //     ->description($revenueChange >= 0
            //         ? 'Up '.number_format(abs($revenueChange), 1).'% from yesterday'
            //         : 'Down '.number_format(abs($revenueChange), 1).'% from yesterday')
            //     ->descriptionIcon($revenueChange >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
            //     ->color($revenueChange >= 0 ? 'success' : 'danger')
            //     ->chart($this->getRevenueTrend())
            //     ->url(route('filament.Operation.resources.orders.index', [
            //         'tableFilters' => ['status' => ['value' => 'delivered']],
            //     ])),

            Stat::make('Collected Revenue', 'KES '.number_format($todayMetrics['collected_revenue'], 2))
                ->description('Payment received today ('.$todayMetrics['collection_rate'].'% collection rate)')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Net Profit Today', 'KES '.number_format($todayMetrics['net_profit'], 2))
                ->description('Margin: '.$todayMetrics['net_margin_percent'].'%')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($todayMetrics['net_profit'] > 0 ? 'success' : 'danger'),

            // ORDERS OVERVIEW
            Stat::make('Pending Review', $pendingOrders)
                ->description('Orders awaiting review')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingOrders > 10 ? 'danger' : 'warning')
                ->chart($this->getOrderTrend('pending_review'))
                ->url(route('filament.Operation.resources.orders.index', [
                    'tableFilters' => ['status' => ['value' => 'pending_review']],
                ])),

            Stat::make('Sent to Suppliers', $sentToSupplier)
                ->description('Awaiting supplier confirmation')
                ->descriptionIcon('heroicon-o-truck')
                ->color('info')
                ->chart($this->getOrderTrend('sent_to_supplier'))
                ->url(route('filament.Operation.resources.orders.index', [
                    'tableFilters' => ['status' => ['value' => 'sent_to_supplier']],
                ])),

            Stat::make('Active Orders', $activeOrders)
                ->description('In progress (confirmed/processing/shipped)')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning')
                ->chart($this->getOrderTrend(['confirmed', 'processing', 'shipped']))
                ->url(route('filament.Operation.resources.orders.index')),

            // Stat::make('Delivered Today', $deliveredToday)
            //     ->description('Successfully completed today')
            //     ->descriptionIcon('heroicon-o-check-circle')
            //     ->color('success')
            //     ->url(route('filament.Operation.resources.orders.index', [
            //         'tableFilters' => ['status' => ['value' => 'delivered']],
            //     ])),

            // DELIVERY OVERVIEW
            Stat::make('Pending Deliveries', $pendingDeliveries)
                ->description('Waiting for rider assignment')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color($pendingDeliveries > 5 ? 'danger' : 'warning')
                ->url(route('filament.Operation.resources.internals.deliveries.index', [
                    'tableFilters' => ['status' => ['value' => 'pending']],
                ])),

            Stat::make('In Transit', $inTransitDeliveries)
                ->description('Currently being delivered')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color('info')
                ->url(route('filament.Operation.resources.internals.deliveries.index')),

            // FINANCIAL OVERVIEW
            Stat::make('Outstanding Receivables', 'KES '.number_format($outstandingReceivables, 2))
                ->description('Pending customer payments')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color($outstandingReceivables > 100000 ? 'warning' : 'info'),

            Stat::make('Outstanding Payables', 'KES '.number_format($outstandingPayables, 2))
                ->description($overduePayables > 0 
                    ? 'KES '.number_format($overduePayables, 2).' overdue!' 
                    : 'All payments current')
                ->descriptionIcon($overduePayables > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($overduePayables > 0 ? 'danger' : 'success'),

            // PRESCRIPTIONS
            // Stat::make('Pending Prescriptions', $pendingPrescriptions)
            //     ->description('Need quotation')
            //     ->descriptionIcon('heroicon-o-document-text')
            //     ->color($pendingPrescriptions > 15 ? 'danger' : 'warning')
            //     ->url(route('filament.Admin.resources.prescriptions.index', [
            //         'tableFilters' => ['status' => ['value' => 'pending']],
            //     ])),

            // Stat::make('Quoted Prescriptions', $quotedPrescriptions)
            //     ->description('Awaiting approval')
            //     ->descriptionIcon('heroicon-o-banknotes')
            //     ->color('info')
            //     ->url(route('filament.Admin.resources.prescriptions.index', [
            //         'tableFilters' => ['status' => ['value' => 'quoted']],
            //     ])),

            // INSURANCE CLAIMS
            // Stat::make('Pending Claims', $pendingClaims)
            //     ->description('Require insurance review')
            //     ->descriptionIcon('heroicon-o-shield-check')
            //     ->color($pendingClaims > 20 ? 'warning' : 'success')
            //     ->url(route('filament.Admin.resources.insurance-claims.index')),

            // RIDERS
            // Stat::make('Available Riders', $availableRiders.' / '.$totalActiveRiders)
            //     ->description('Ready for dispatch')
            //     ->descriptionIcon('heroicon-o-user-group')
            //     ->color($availableRiders < 3 ? 'danger' : 'success')
            //     ->url(route('filament.Operation.resources.internals.riders.index')),
        ];
    }

    /**
     * Get order trend for the last 7 days
     */
    private function getOrderTrend($status): array
    {
        $query = Order::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [now()->subDays(7), now()]);

        if (is_array($status)) {
            $query->whereIn('status', $status);
        } else {
            $query->where('status', $status);
        }

        return $query->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    /**
     * Get revenue trend for the last 7 days sing receivables
     */
    private function getRevenueTrend(): array
    {
        $revenueService = app(RevenueService::class);
        return array_values($revenueService->getDailyRevenueTrend(7));
    }
}