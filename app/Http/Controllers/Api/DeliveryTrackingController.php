<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Services\DeliveryTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeliveryTrackingController extends Controller
{
    protected DeliveryTrackingService $trackingService;

    public function __construct(DeliveryTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Get tracking information for a delivery
     */
    public function show(Delivery $delivery): JsonResponse
    {
        try {
            $trackingSummary = $this->trackingService->getTrackingSummary($delivery);

            return response()->json([
                'success' => true,
                'data' => $trackingSummary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tracking information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current location of delivery
     */
    public function currentLocation(Delivery $delivery): JsonResponse
    {
        try {
            $location = $this->trackingService->getCurrentLocation($delivery);

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'No location data available',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $location,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching current location',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get delivery route/history
     */
    public function route(Delivery $delivery): JsonResponse
    {
        try {
            $route = $this->trackingService->getDeliveryRoute($delivery);

            return response()->json([
                'success' => true,
                'data' => [
                    'delivery_number' => $delivery->delivery_number,
                    'status' => $delivery->status,
                    'route' => $route,
                    'total_points' => $route->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching delivery route',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get ETA for delivery
     */
    public function eta(Delivery $delivery): JsonResponse
    {
        try {
            $eta = $this->trackingService->calculateETA($delivery);

            if (!$eta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot calculate ETA - insufficient data',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $eta,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating ETA',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record location update (for rider app)
     */
    public function recordLocation(Request $request, Delivery $delivery): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|integer|between:0,359',
        ]);

        try {
            $tracking = $this->trackingService->recordLocation(
                $delivery,
                $validated['latitude'],
                $validated['longitude'],
                $validated['accuracy'] ?? null,
                $validated['speed'] ?? null,
                $validated['heading'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Location recorded successfully',
                'data' => [
                    'tracking_id' => $tracking->id,
                    'recorded_at' => $tracking->recorded_at->toIso8601String(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recording location',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track by order number (public endpoint for patients/physicians)
     */
    public function trackByOrderNumber(string $orderNumber): JsonResponse
    {
        try {
            $delivery = Delivery::whereHas('order', function ($query) use ($orderNumber) {
                    $query->where('order_number', $orderNumber);
                })
                ->with(['order', 'rider'])
                ->firstOrFail();

            $trackingSummary = $this->trackingService->getTrackingSummary($delivery);

            return response()->json([
                'success' => true,
                'data' => $trackingSummary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found',
            ], 404);
        }
    }

    /**
     * Track by delivery number (public endpoint)
     */
    public function trackByDeliveryNumber(string $deliveryNumber): JsonResponse
    {
        try {
            $delivery = Delivery::where('delivery_number', $deliveryNumber)
                ->with(['order', 'rider'])
                ->firstOrFail();

            $trackingSummary = $this->trackingService->getTrackingSummary($delivery);

            return response()->json([
                'success' => true,
                'data' => $trackingSummary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found',
            ], 404);
        }
    }
}