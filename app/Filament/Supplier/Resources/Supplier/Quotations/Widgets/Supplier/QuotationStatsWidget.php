<?php

namespace App\Filament\Supplier\Resources\Supplier\Quotations\Widgets\Supplier;

use App\Models\Quotation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class QuotationStatsWidget extends StatsOverviewWidget
{
  protected function getStats(): array
    {
        $supplierId = Auth::user()->userProfile->id ?? null;

        if (!$supplierId) {
            return [];
        }

        // Pending quotations (awaiting response)
        $pending = Quotation::whereHas('items', function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
        })
        ->where('status', 'pending')
        ->where('valid_until', '>', now())
        ->count();

        // Accepted quotations (converted to orders)
        $accepted = Quotation::whereHas('items', function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
        })
        ->where('status', 'accepted')
        ->count();

        $lastWeekAccepted = Quotation::whereHas('items', function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
        })
        ->where('status', 'accepted')
        ->where('updated_at', '>=', now()->subWeek())
        ->count();

        // Response rate (sent / total pending)
        $totalRequests = Quotation::whereHas('items', function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
        })
        ->whereMonth('created_at', now()->month)
        ->count();

        $responded = Quotation::whereHas('items', function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
        })
        ->whereIn('status', ['sent', 'accepted'])
        ->whereMonth('created_at', now()->month)
        ->count();

        $responseRate = $totalRequests > 0 ? ($responded / $totalRequests) * 100 : 0;

        // Average response time (in hours)
        $avgResponseTime = Quotation::whereHas('items', function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
        })
        ->where('status', 'sent')
        ->whereMonth('updated_at', now()->month)
        ->get()
        ->avg(function ($quotation) {
            return $quotation->created_at->diffInHours($quotation->updated_at);
        }) ?? 0;

        return [
            Stat::make('Pending Responses', $pending)
                ->description('Awaiting your response')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Accepted This Week', $accepted)
                ->description($lastWeekAccepted . ' new this week')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Response Rate', number_format($responseRate, 1) . '%')
                ->description('This month')
                ->descriptionIcon($responseRate >= 80 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($responseRate >= 80 ? 'success' : 'warning'),

            Stat::make('Avg Response Time', number_format($avgResponseTime, 1) . ' hrs')
                ->description('This month')
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgResponseTime <= 24 ? 'success' : 'warning'),
        ];
    }
}
