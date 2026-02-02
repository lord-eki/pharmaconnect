<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Prescription;
use App\Models\PricingRule;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class RecentActivitiesWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-activities-widget';
    protected int|string|array $columnSpan = 1;
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '30s';

    public function getActivities(): array
    {
        $activities = [];

        // Recent user registrations (approved users)
        $recentUsers = User::where('is_active', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentUsers as $user) {
            $role = $user->roles->first()?->name ?? 'User';
            $activities[] = [
                'type' => 'success',
                'icon' => 'heroicon-o-user-plus',
                'title' => 'New user registration approved',
                'description' => "{$user->full_name} - {$role}",
                'time' => $user->created_at->diffForHumans(),
                'color' => 'success'
            ];
        }

        // Recent prescription submissions
        $recentPrescriptions = Prescription::whereIn('status', ['submitted', 'processing'])
            ->where('created_at', '>=', now()->subDays(3))
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentPrescriptions as $prescription) {
            $activities[] = [
                'type' => 'info',
                'icon' => 'heroicon-o-document-text',
                'title' => 'New prescription submitted',
                'description' => "Prescription #{$prescription->prescription_number} - {$prescription->status}",
                'time' => $prescription->created_at->diffForHumans(),
                'color' => 'info'
            ];
        }

        // Recent pricing rule updates
        $recentPricingUpdate = PricingRule::where('updated_at', '>=', now()->subDays(7))
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($recentPricingUpdate) {
            $activities[] = [
                'type' => 'info',
                'icon' => 'heroicon-o-cog-6-tooth',
                'title' => 'Pricing configuration updated',
                'description' => "{$recentPricingUpdate->name} - {$recentPricingUpdate->markup_percentage}% markup",
                'time' => $recentPricingUpdate->updated_at->diffForHumans(),
                'color' => 'info'
            ];
        }

        // Recent bulk operations (multiple orders from same prescription)
        $bulkOrders = DB::table('orders')
            ->select('prescription_id', DB::raw('COUNT(*) as order_count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('prescription_id')
            ->groupBy('prescription_id')
            ->having('order_count', '>', 3)
            ->orderBy('order_count', 'desc')
            ->first();

        if ($bulkOrders) {
            $activities[] = [
                'type' => 'warning',
                'icon' => 'heroicon-o-document-duplicate',
                'title' => 'Bulk order operation completed',
                'description' => "{$bulkOrders->order_count} orders processed for prescription",
                'time' => 'Recently',
                'color' => 'warning'
            ];
        }

        // System alerts - overdue deliveries
        $overdueCount = Order::whereIn('status', ['confirmed', 'processing', 'shipped'])
            ->where('expected_delivery', '<', now())
            ->count();

        if ($overdueCount > 0) {
            $activities[] = [
                'type' => 'danger',
                'icon' => 'heroicon-o-exclamation-triangle',
                'title' => 'Overdue deliveries detected',
                'description' => "{$overdueCount} orders past expected delivery - requires attention",
                'time' => 'Active alert',
                'color' => 'danger'
            ];
        }

        // Failed orders
        $failedOrders = Order::where('status', 'cancelled')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($failedOrders > 5) {
            $activities[] = [
                'type' => 'danger',
                'icon' => 'heroicon-o-x-circle',
                'title' => 'High order cancellation rate',
                'description' => "{$failedOrders} orders cancelled in last 7 days",
                'time' => 'Last 7 days',
                'color' => 'danger'
            ];
        }

        // Sort by most recent and limit to 5
        usort($activities, function($a, $b) {
            $timeA = $a['time'];
            $timeB = $b['time'];
            
            // Priority: "mins ago" > "hours ago" > "days ago" > "Recently" > "Active alert"
            $priority = [
                'mins ago' => 1, 'min ago' => 1, 'seconds ago' => 1, 'second ago' => 1,
                'hours ago' => 2, 'hour ago' => 2,
                'days ago' => 3, 'day ago' => 3,
                'Recently' => 4,
                'Active alert' => 5,
                'Last 7 days' => 6
            ];
            
            foreach ($priority as $key => $value) {
                if (str_contains($timeA, $key)) $scoreA = $value;
                if (str_contains($timeB, $key)) $scoreB = $value;
            }
            
            return ($scoreA ?? 99) <=> ($scoreB ?? 99);
        });

        return array_slice($activities, 0, 5);
    }
}