<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryTracking;
use Illuminate\Support\Facades\Log;

class DeliveryTrackingService
{
    /**
     * Record GPS tracking point
     */
    public function recordLocation(
        Delivery $delivery,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?float $speed = null,
        ?int $heading = null
    ): DeliveryTracking {
        return DeliveryTracking::create([
            'delivery_id' => $delivery->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'speed' => $speed,
            'heading' => $heading,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Get delivery route/history
     */
    public function getDeliveryRoute(Delivery $delivery)
    {
        return $delivery->tracking()
            ->orderBy('recorded_at', 'asc')
            ->get()
            ->map(function ($point) {
                return [
                    'lat' => (float) $point->latitude,
                    'lng' => (float) $point->longitude,
                    'timestamp' => $point->recorded_at->toIso8601String(),
                    'speed' => $point->speed,
                    'heading' => $point->heading,
                ];
            });
    }

    /**
     * Get current location of delivery
     */
    public function getCurrentLocation(Delivery $delivery): ?array
    {
        $latest = $delivery->tracking()
            ->latest('recorded_at')
            ->first();

        if (!$latest) {
            return null;
        }

        return [
            'lat' => (float) $latest->latitude,
            'lng' => (float) $latest->longitude,
            'accuracy' => $latest->accuracy,
            'speed' => $latest->speed,
            'heading' => $latest->heading,
            'timestamp' => $latest->recorded_at->toIso8601String(),
            'time_ago' => $latest->recorded_at->diffForHumans(),
        ];
    }

    /**
     * Calculate estimated time of arrival
     */
    public function calculateETA(Delivery $delivery): ?array
    {
        $currentLocation = $this->getCurrentLocation($delivery);
        
        if (!$currentLocation || !$delivery->delivery_latitude || !$delivery->delivery_longitude) {
            return null;
        }

        // Calculate distance to destination
        $distance = $this->calculateDistance(
            $currentLocation['lat'],
            $currentLocation['lng'],
            $delivery->delivery_latitude,
            $delivery->delivery_longitude
        );

        // Average speed (assume 30 km/h if no speed data)
        $avgSpeed = $currentLocation['speed'] ?? 30;
        
        if ($avgSpeed > 0) {
            $hoursToDestination = $distance / $avgSpeed;
            $eta = now()->addHours($hoursToDestination);
            
            return [
                'distance_km' => round($distance, 2),
                'estimated_minutes' => round($hoursToDestination * 60),
                'eta' => $eta->toIso8601String(),
                'eta_formatted' => $eta->format('M d, Y H:i'),
            ];
        }

        return null;
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    protected function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Get delivery tracking summary
     */
    public function getTrackingSummary(Delivery $delivery): array
    {
        $currentLocation = $this->getCurrentLocation($delivery);
        $eta = $this->calculateETA($delivery);
        $route = $this->getDeliveryRoute($delivery);

        return [
            'delivery_number' => $delivery->delivery_number,
            'status' => $delivery->status,
            'rider' => $delivery->rider ? [
                'name' => $delivery->rider->full_name,
                'phone' => $delivery->rider->phone,
                'vehicle' => $delivery->rider->vehicle_type,
                'vehicle_registration' => $delivery->rider->vehicle_registration,
                'rating' => $delivery->rider->rating,
            ] : null,
            'current_location' => $currentLocation,
            'eta' => $eta,
            'route' => $route,
            'pickup' => [
                'address' => $delivery->pickup_address,
                'scheduled' => $delivery->scheduled_pickup?->toIso8601String(),
                'actual' => $delivery->actual_pickup?->toIso8601String(),
            ],
            'delivery' => [
                'address' => $delivery->delivery_address,
                'estimated' => $delivery->estimated_delivery?->toIso8601String(),
                'actual' => $delivery->actual_delivery?->toIso8601String(),
                'recipient' => $delivery->recipient_name,
                'recipient_phone' => $delivery->recipient_phone,
            ],
            'distance_km' => $delivery->estimated_distance_km,
            'delivery_fee' => $delivery->delivery_fee,
        ];
    }
}