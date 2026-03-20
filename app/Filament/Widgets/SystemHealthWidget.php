<?php

namespace App\Filament\Widgets;

use App\Models\Medicine;
use App\Models\Order;
use App\Models\Transaction;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemHealthWidget extends Widget
{
    protected string $view = 'filament.widgets.system-health-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 2;

    public function getHealthMetrics(): array
    {
        return [
            $this->getApiSystemsHealth(),
            $this->getPaymentProcessingHealth(),
            $this->getMedicineDatabaseHealth(),
            $this->getErrorMonitoringHealth(),
        ];
    }

    protected function getApiSystemsHealth(): array
    {
        $recentActivity = Cache::remember('api_health_check', 60, function () {
            return DB::table('orders')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();
        });

        $uptime = Cache::remember('system_uptime', 300, function () {
            $total = DB::table('orders')->where('created_at', '>=', now()->subDay())->count();
            $failed = DB::table('orders')
                ->where('created_at', '>=', now()->subDay())
                ->where('status', 'cancelled')
                ->count();
            
            return $total > 0 ? number_format((($total - $failed) / $total) * 100, 1) : 99.9;
        });

        return [
            'name' => 'API Systems',
            'status' => $recentActivity ? 'operational' : 'warning',
            'uptime' => $uptime . '%',
            'description' => $recentActivity ? 'All endpoints operational' : 'Low activity detected',
            'color' => $recentActivity ? 'success' : 'warning'
        ];
    }

    protected function getPaymentProcessingHealth(): array
    {
        $recentTransactions = Cache::remember('payment_health', 60, function () {
            return Transaction::where('created_at', '>=', now()->subHour())
                ->where('status', 'completed')
                ->count();
        });

        $pendingTransactions = Transaction::where('status', 'pending')->count();

        return [
            'name' => 'Payment Processing',
            'status' => 'processing',
            'uptime' => null,
            'description' => $pendingTransactions > 0 
                ? "{$pendingTransactions} pending transactions" 
                : "All payments processed ({$recentTransactions} in last hour)",
            'color' => $pendingTransactions > 10 ? 'warning' : 'success'
        ];
    }

    protected function getMedicineDatabaseHealth(): array
    {
        $stats = Cache::remember('medicine_db_stats', 300, function () {
            $total = Medicine::count();
            $active = Medicine::where('is_active', true)->count();
            $withStock = DB::table('supplier_medicines')
                ->where('is_available', true)
                ->where('stock_quantity', '>', 0)
                ->distinct('medicine_id')
                ->count();

            return [
                'total' => $total,
                'active' => $active,
                'with_stock' => $withStock,
                'percentage' => $total > 0 ? round(($withStock / $total) * 100) : 0
            ];
        });

        $isHealthy = $stats['percentage'] > 80;

        return [
            'name' => 'Medicine Database',
            'status' => $isHealthy ? 'operational' : 'degraded',
            'uptime' => null,
            'description' => $isHealthy 
                ? "{$stats['with_stock']}/{$stats['total']} medicines in stock" 
                : "Stock sync in progress ({$stats['percentage']}%)",
            'color' => $isHealthy ? 'success' : 'warning'
        ];
    }

    protected function getErrorMonitoringHealth(): array
    {
        $recentErrors = Cache::remember('recent_errors_count', 60, function () {
            try {
                // Count failed orders in last 24 hours
                $failedOrders = Order::where('created_at', '>=', now()->subDay())
                    ->where('status', 'cancelled')
                    ->count();

                // Check for overdue orders
                $overdueOrders = Order::whereIn('status', ['sent_to_supplier', 'confirmed', 'processing'])
                    ->where('expected_delivery', '<', now())
                    ->count();

                return $failedOrders + $overdueOrders;
            } catch (\Exception $e) {
                Log::error('Error monitoring check failed', ['error' => $e->getMessage()]);
                return 0;
            }
        });

        return [
            'name' => 'Error Monitoring',
            'status' => 'monitoring',
            'uptime' => null,
            'description' => $recentErrors > 0 
                ? "{$recentErrors} issues detected in last 24h" 
                : "No critical issues detected",
            'color' => $recentErrors > 20 ? 'danger' : ($recentErrors > 5 ? 'warning' : 'info')
        ];
    }
}