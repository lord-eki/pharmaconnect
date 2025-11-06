<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RiderController extends Controller
{
    /**
     * Get all riders with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        
        $riders = Rider::query()
            ->with('user')
            ->when($request->get('status'), function ($query, $status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'available') {
                    $query->where('is_active', true)->where('is_available', true);
                }
            })
            ->when($request->get('county'), function ($query, $county) {
                $query->where('base_county', $county);
            })
            ->when($request->get('city'), function ($query, $city) {
                $query->where('base_city', $city);
            })
            ->orderBy('rating', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $riders,
        ]);
    }

    /**
     * Get available riders
     */
    public function available(Request $request): JsonResponse
    {
        $county = $request->get('county');
        $city = $request->get('city');

        $riders = Rider::active()
            ->available()
            ->when($county, function ($query, $county) {
                $query->where('base_county', $county);
            })
            ->when($city, function ($query, $city) {
                $query->where('base_city', $city);
            })
            ->orderBy('rating', 'desc')
            ->orderBy('total_deliveries', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $riders,
            'count' => $riders->count(),
        ]);
    }

    /**
     * Get single rider details
     */
    public function show(Rider $rider): JsonResponse
    {
        $rider->load(['user', 'deliveries' => function ($query) {
            $query->latest()->limit(10);
        }]);

        $stats = [
            'total_deliveries' => $rider->total_deliveries,
            'rating' => $rider->rating,
            'active_deliveries' => $rider->deliveries()
                ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                ->count(),
            'completed_today' => $rider->deliveries()
                ->where('status', 'delivered')
                ->whereDate('actual_delivery', today())
                ->count(),
            'completed_this_month' => $rider->deliveries()
                ->where('status', 'delivered')
                ->whereMonth('actual_delivery', now()->month)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'rider' => $rider,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Get rider's deliveries
     */
    public function deliveries(Request $request, Rider $rider): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $status = $request->get('status');

        $deliveries = $rider->deliveries()
            ->with(['order.prescription.patient', 'order.supplier'])
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $deliveries,
        ]);
    }

    /**
     * Get rider's active deliveries
     */
    public function activeDeliveries(Rider $rider): JsonResponse
    {
        $deliveries = $rider->deliveries()
            ->with(['order.prescription.patient', 'order.supplier'])
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deliveries,
        ]);
    }

    /**
     * Toggle rider availability
     */
    public function toggleAvailability(Rider $rider): JsonResponse
    {
        try {
            // Check if rider has active deliveries
            $activeDeliveries = $rider->deliveries()
                ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                ->count();

            if ($activeDeliveries > 0 && $rider->is_available) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot mark as unavailable while having active deliveries',
                    'active_deliveries' => $activeDeliveries,
                ], 400);
            }

            $rider->update([
                'is_available' => !$rider->is_available,
            ]);

            return response()->json([
                'success' => true,
                'message' => $rider->is_available 
                    ? 'Rider marked as available' 
                    : 'Rider marked as unavailable',
                'is_available' => $rider->is_available,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling availability',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update rider location (for rider app)
     */
    public function updateLocation(Request $request, Rider $rider): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update location for active deliveries
        $activeDeliveries = $rider->deliveries()
            ->whereIn('status', ['picked_up', 'in_transit'])
            ->get();

        foreach ($activeDeliveries as $delivery) {
            $delivery->tracking()->create([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'speed' => $request->speed,
                'heading' => $request->heading,
                'recorded_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Location updated',
            'deliveries_updated' => $activeDeliveries->count(),
        ]);
    }

    /**
     * Get rider statistics
     */
    public function statistics(Rider $rider): JsonResponse
    {
        $stats = [
            'overview' => [
                'total_deliveries' => $rider->total_deliveries,
                'rating' => $rider->rating,
                'active_deliveries' => $rider->deliveries()
                    ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                    ->count(),
            ],
            'today' => [
                'completed' => $rider->deliveries()
                    ->where('status', 'delivered')
                    ->whereDate('actual_delivery', today())
                    ->count(),
                'failed' => $rider->deliveries()
                    ->where('status', 'failed')
                    ->whereDate('updated_at', today())
                    ->count(),
            ],
            'this_week' => [
                'completed' => $rider->deliveries()
                    ->where('status', 'delivered')
                    ->whereBetween('actual_delivery', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
            ],
            'this_month' => [
                'completed' => $rider->deliveries()
                    ->where('status', 'delivered')
                    ->whereMonth('actual_delivery', now()->month)
                    ->whereYear('actual_delivery', now()->year)
                    ->count(),
                'total_distance' => $rider->deliveries()
                    ->where('status', 'delivered')
                    ->whereMonth('actual_delivery', now()->month)
                    ->whereYear('actual_delivery', now()->year)
                    ->sum('estimated_distance_km'),
            ],
            'performance' => [
                'on_time_rate' => $this->calculateOnTimeRate($rider),
                'average_delivery_time' => $this->calculateAverageDeliveryTime($rider),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculate on-time delivery rate
     */
    protected function calculateOnTimeRate(Rider $rider): float
    {
        $totalDeliveries = $rider->deliveries()
            ->where('status', 'delivered')
            ->count();

        if ($totalDeliveries === 0) {
            return 0;
        }

        $onTimeDeliveries = $rider->deliveries()
            ->where('status', 'delivered')
            ->whereColumn('actual_delivery', '<=', 'estimated_delivery')
            ->count();

        return round(($onTimeDeliveries / $totalDeliveries) * 100, 2);
    }

    /**
     * Calculate average delivery time in minutes
     */
    protected function calculateAverageDeliveryTime(Rider $rider): ?float
    {
        $deliveries = $rider->deliveries()
            ->where('status', 'delivered')
            ->whereNotNull('actual_pickup')
            ->whereNotNull('actual_delivery')
            ->get();

        if ($deliveries->isEmpty()) {
            return null;
        }

        $totalMinutes = $deliveries->sum(function ($delivery) {
            return $delivery->actual_pickup->diffInMinutes($delivery->actual_delivery);
        });

        return round($totalMinutes / $deliveries->count(), 2);
    }
}