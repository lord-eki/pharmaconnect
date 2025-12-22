<?php

namespace App\Filament\Operation\Widgets;

use App\Models\Delivery;
use App\Models\InsuranceClaim;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Rider;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationStats extends StatsOverviewWidget
{
protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        // Today's metrics
        $today = now()->startOfDay();
        
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
        $deliveredTodayCount = Delivery::where('status', 'delivered')
            ->whereDate('actual_delivery', $today)
            ->count();
        
        // Prescription metrics
        $pendingPrescriptions = Prescription::where('status', 'pending')->count();
        $quotedPrescriptions = Prescription::where('status', 'quoted')->count();
        
        // Insurance claims
        $pendingClaims = InsuranceClaim::whereIn('status', ['submitted', 'under_review'])->count();
        
        // Revenue metrics (today)
        $todayRevenue = Order::where('status', 'delivered')
            ->whereDate('delivered_at', $today)
            ->sum('total_amount');
        
        // Week comparison
        $lastWeekRevenue = Order::where('status', 'delivered')
            ->whereBetween('delivered_at', [
                now()->subWeek()->startOfDay(),
                now()->subWeek()->endOfDay()
            ])
            ->sum('total_amount');
        
        $revenueChange = $lastWeekRevenue > 0 
            ? (($todayRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100 
            : 0;
        
        // Active riders
        $availableRiders = Rider::where('is_active', true)
            ->where('is_available', true)
            ->count();
        
        $totalActiveRiders = Rider::where('is_active', true)->count();
        
        // Suppliers
        $activeSuppliersToday = Order::where('status', 'sent_to_supplier')
            ->orWhere('status', 'confirmed')
            ->whereDate('created_at', $today)
            ->distinct('supplier_id')
            ->count('supplier_id');

        return [
            // Orders Overview
            Stat::make('Pending Review', $pendingOrders)
                ->description('Orders awaiting review')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingOrders > 10 ? 'danger' : 'warning')
                ->chart($this->getOrderTrend('pending_review'))
                ->url(route('filament.Operation.resources.orders.index', [
                    'tableFilters' => ['status' => ['value' => 'pending_review']]
                ])),
            
            Stat::make('Sent to Suppliers', $sentToSupplier)
                ->description('Awaiting supplier confirmation')
                ->descriptionIcon('heroicon-o-truck')
                ->color('info')
                ->chart($this->getOrderTrend('sent_to_supplier'))
                ->url(route('filament.Operation.resources.orders.index', [
                    'tableFilters' => ['status' => ['value' => 'sent_to_supplier']]
                ])),
            
            Stat::make('Active Orders', $activeOrders)
                ->description('In progress (confirmed/processing/shipped)')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning')
                ->chart($this->getOrderTrend(['confirmed', 'processing', 'shipped']))
                ->url(route('filament.Operation.resources.orders.index')),
            
            Stat::make('Delivered Today', $deliveredToday)
                ->description('Successfully completed today')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->url(route('filament.Operation.resources.orders.index', [
                    'tableFilters' => ['status' => ['value' => 'delivered']]
                ])),
            
            // Delivery Overview
            Stat::make('Pending Deliveries', $pendingDeliveries)
                ->description('Waiting for rider assignment')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color($pendingDeliveries > 5 ? 'danger' : 'warning')
                ->url(route('filament.Operation.resources.internals.deliveries.index', [
                    'tableFilters' => ['status' => ['value' => 'pending']]
                ])),
            
            Stat::make('In Transit', $inTransitDeliveries)
                ->description('Currently being delivered')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color('info')
                ->url(route('filament.Operation.resources.internals.deliveries.index')),
            
            // Prescriptions
            Stat::make('Pending Prescriptions', $pendingPrescriptions)
                ->description('Need quotation')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($pendingPrescriptions > 15 ? 'danger' : 'warning')
                ->url(route('filament.Admin.resources.prescriptions.index', [
                    'tableFilters' => ['status' => ['value' => 'pending']]
                ])),
            
            Stat::make('Quoted Prescriptions', $quotedPrescriptions)
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info')
                ->url(route('filament.Admin.resources.prescriptions.index', [
                    'tableFilters' => ['status' => ['value' => 'quoted']]
                ])),
            
            // Insurance Claims
            Stat::make('Pending Claims', $pendingClaims)
                ->description('Require insurance review')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($pendingClaims > 20 ? 'warning' : 'success')
                ->url(route('filament.Admin.resources.insurance-claims.index')),
            
            // Revenue
            Stat::make('Today\'s Revenue', 'KES ' . number_format($todayRevenue, 2))
                ->description($revenueChange >= 0 
                    ? 'Up ' . number_format(abs($revenueChange), 1) . '% from last week'
                    : 'Down ' . number_format(abs($revenueChange), 1) . '% from last week')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($this->getRevenueTrend())
                ->url(route('filament.Operation.resources.orders.index', [
                    'tableFilters' => ['status' => ['value' => 'delivered']]
                ])),
            
            // Riders
            Stat::make('Available Riders', $availableRiders . ' / ' . $totalActiveRiders)
                ->description('Ready for dispatch')
                ->descriptionIcon('heroicon-o-user-group')
                ->color($availableRiders < 3 ? 'danger' : 'success')
                ->url(route('filament.Operation.resources.internals.riders.index')),
            
            // Suppliers
            Stat::make('Active Suppliers Today', $activeSuppliersToday)
                ->description('Processing orders')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('info')
                ->url(route('filament.Admin.resources.suppliers.index')),
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
     * Get revenue trend for the last 7 days
     */
    private function getRevenueTrend(): array
    {
        return Order::where('status', 'delivered')
            ->selectRaw('DATE(delivered_at) as date, SUM(total_amount) as total')
            ->whereBetween('delivered_at', [now()->subDays(7), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->toArray();
    } 
   
}
