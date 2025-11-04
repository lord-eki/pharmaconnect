<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Map Container -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div id="map" style="height: 500px; width: 100%;"></div>
        </div>

        <!-- Delivery Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Status Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Delivery Status</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-500">Status</span>
                        <p class="font-medium">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ match($delivery->status) {
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'assigned' => 'bg-blue-100 text-blue-800',
                                    'picked_up' => 'bg-indigo-100 text-indigo-800',
                                    'in_transit' => 'bg-purple-100 text-purple-800',
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'failed', 'cancelled' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                } }}">
                                {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Delivery Number</span>
                        <p class="font-medium">{{ $delivery->delivery_number }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Distance</span>
                        <p class="font-medium">{{ $delivery->estimated_distance_km ? number_format($delivery->estimated_distance_km, 2) . ' km' : 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Delivery Fee</span>
                        <p class="font-medium">KES {{ number_format($delivery->delivery_fee, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Rider Info Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Rider Information</h3>
                @if($delivery->rider)
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm text-gray-500">Name</span>
                            <p class="font-medium">{{ $delivery->rider->full_name }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Phone</span>
                            <p class="font-medium">
                                <a href="tel:{{ $delivery->rider->phone }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $delivery->rider->phone }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Vehicle</span>
                            <p class="font-medium">{{ ucfirst($delivery->rider->vehicle_type ?? 'N/A') }} - {{ $delivery->rider->vehicle_registration ?? 'N/A' }}</p>
                        </div>
                        @if($latestTracking)
                            <div>
                                <span class="text-sm text-gray-500">Last Update</span>
                                <p class="font-medium">{{ $latestTracking->recorded_at->diffForHumans() }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">Speed</span>
                                <p class="font-medium">{{ $latestTracking->speed ?? 0 }} km/h</p>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-gray-500 italic">No rider assigned yet</p>
                @endif
            </div>

            <!-- Timeline Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Timeline</h3>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-500">Scheduled Pickup</span>
                        <p class="font-medium">{{ $delivery->scheduled_pickup ? $delivery->scheduled_pickup->format('M d, Y H:i') : 'Not scheduled' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Actual Pickup</span>
                        <p class="font-medium {{ $delivery->actual_pickup ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $delivery->actual_pickup ? $delivery->actual_pickup->format('M d, Y H:i') : 'Not picked up' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Estimated Delivery</span>
                        <p class="font-medium">{{ $delivery->estimated_delivery ? $delivery->estimated_delivery->format('M d, Y H:i') : 'Not estimated' }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Actual Delivery</span>
                        <p class="font-medium {{ $delivery->actual_delivery ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $delivery->actual_delivery ? $delivery->actual_delivery->format('M d, Y H:i') : 'Not delivered' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pickup Address
                </h3>
                <p class="text-gray-700">{{ $delivery->pickup_address }}</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Delivery Address
                </h3>
                <p class="text-gray-700">{{ $delivery->delivery_address }}</p>
            </div>
        </div>

        <!-- Tracking History -->
        @if($trackingHistory->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Tracking History</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Latitude</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Longitude</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Speed (km/h)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($trackingHistory as $track)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $track->recorded_at->format('M d, H:i:s') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($track->latitude, 6) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($track->longitude, 6) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $track->speed ?? 0 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        let map;
        let markers = {
            pickup: null,
            delivery: null,
            current: null
        };
        let directionsService;
        let directionsRenderer;
        let trackingPolyline;

        function initMap() {
            // Initialize map centered on current location
            const currentLocation = { 
                lat: {{ $currentLat }}, 
                lng: {{ $currentLng }} 
            };

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: currentLocation,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true,
            });

            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#4F46E5',
                    strokeWeight: 4,
                    strokeOpacity: 0.7
                }
            });

            // Pickup marker (blue)
            markers.pickup = new google.maps.Marker({
                position: { lat: {{ $pickupLat }}, lng: {{ $pickupLng }} },
                map: map,
                title: 'Pickup Location',
                icon: {
                    url: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                    scaledSize: new google.maps.Size(40, 40)
                }
            });

            // Delivery marker (green)
            markers.delivery = new google.maps.Marker({
                position: { lat: {{ $deliveryLat }}, lng: {{ $deliveryLng }} },
                map: map,
                title: 'Delivery Location',
                icon: {
                    url: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
                    scaledSize: new google.maps.Size(40, 40)
                }
            });

            // Current location marker (red - rider)
            @if($latestTracking)
            markers.current = new google.maps.Marker({
                position: { lat: {{ $latestTracking->latitude }}, lng: {{ $latestTracking->longitude }} },
                map: map,
                title: 'Current Location',
                icon: {
                    url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                    scaledSize: new google.maps.Size(50, 50)
                },
                animation: google.maps.Animation.BOUNCE
            });

            // Info windows
            const pickupInfo = new google.maps.InfoWindow({
                content: '<div class="p-2"><strong>Pickup Location</strong><br>{{ addslashes($delivery->pickup_address) }}</div>'
            });
            const deliveryInfo = new google.maps.InfoWindow({
                content: '<div class="p-2"><strong>Delivery Location</strong><br>{{ addslashes($delivery->delivery_address) }}</div>'
            });
            const currentInfo = new google.maps.InfoWindow({
                content: '<div class="p-2"><strong>Rider Current Location</strong><br>Speed: {{ $latestTracking->speed ?? 0 }} km/h<br>Updated: {{ $latestTracking->recorded_at->diffForHumans() }}</div>'
            });

            markers.pickup.addListener('click', () => pickupInfo.open(map, markers.pickup));
            markers.delivery.addListener('click', () => deliveryInfo.open(map, markers.delivery));
            markers.current.addListener('click', () => currentInfo.open(map, markers.current));
            @endif

            // Draw route from pickup to delivery
            const request = {
                origin: { lat: {{ $pickupLat }}, lng: {{ $pickupLng }} },
                destination: { lat: {{ $deliveryLat }}, lng: {{ $deliveryLng }} },
                travelMode: google.maps.TravelMode.DRIVING
            };

            directionsService.route(request, function(result, status) {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                }
            });

            // Draw tracking history path (red line)
            @if($trackingHistory->isNotEmpty())
            const trackingPath = [
                @foreach($trackingHistory->reverse() as $track)
                { lat: {{ $track->latitude }}, lng: {{ $track->longitude }} },
                @endforeach
            ];

            trackingPolyline = new google.maps.Polyline({
                path: trackingPath,
                geodesic: true,
                strokeColor: '#EF4444',
                strokeOpacity: 0.8,
                strokeWeight: 3,
                map: map
            });
            @endif

            // Fit bounds to show all markers
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(markers.pickup.getPosition());
            bounds.extend(markers.delivery.getPosition());
            @if($latestTracking)
            bounds.extend(markers.current.getPosition());
            @endif
            map.fitBounds(bounds);
        }

        // Load Google Maps API
        function loadGoogleMapsScript() {
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        // Initialize on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadGoogleMapsScript);
        } else {
            loadGoogleMapsScript();
        }

        // Auto-refresh every 30 seconds to get latest tracking data
        setInterval(() => {
            window.location.reload();
        }, 30000);
    </script>
    @endpush
</x-filament-panels::page>