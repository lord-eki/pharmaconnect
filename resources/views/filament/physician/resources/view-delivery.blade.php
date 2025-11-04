<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Live Tracking Map Section --}}
        @if($this->record->status !== 'delivered' && $this->record->status !== 'cancelled')
        <x-filament::section>
            <x-slot name="heading">
                Live Tracking
            </x-slot>

            <div class="space-y-4">
                {{-- Map Container --}}
                <div id="tracking-map" class="w-full h-96 rounded-lg bg-gray-100 relative">
                    {{-- Map will be loaded here via JavaScript --}}
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <p class="text-gray-600 font-medium">Live GPS Tracking</p>
                            <p class="text-sm text-gray-500 mt-1">Map loading...</p>
                        </div>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span class="text-xs text-blue-600 font-medium">DISTANCE</span>
                        </div>
                        <p class="text-2xl font-bold text-blue-900">
                            {{ number_format($this->record->estimated_distance_km, 1) }} km
                        </p>
                    </div>

                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-xs text-purple-600 font-medium">ETA</span>
                        </div>
                        <p class="text-2xl font-bold text-purple-900">
                            @if($this->record->estimated_delivery)
                                {{ $this->record->estimated_delivery->format('H:i') }}
                            @else
                                --:--
                            @endif
                        </p>
                    </div>

                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-xs text-green-600 font-medium">LAST UPDATE</span>
                        </div>
                        <p class="text-xl font-bold text-green-900">
                            @if($this->record->tracking->first())
                                {{ $this->record->tracking->first()->recorded_at->diffForHumans() }}
                            @else
                                No updates
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Auto-refresh indicator --}}
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-gray-600">Auto-refreshing every 30 seconds</span>
                    </div>
                    <button 
                        type="button"
                        wire:click="$refresh"
                        class="text-sm text-blue-600 hover:text-blue-700 font-medium"
                    >
                        Refresh Now
                    </button>
                </div>
            </div>
        </x-filament::section>
        @endif

        {{-- Infolist (default Filament view) --}}
        {{ $this->infolist }}
    </div>

    {{-- Auto-refresh script --}}
    @if($this->record->status !== 'delivered' && $this->record->status !== 'cancelled')
    <script>
        // Auto-refresh every 30 seconds for active deliveries
        setInterval(function() {
            @this.call('$refresh');
        }, 30000);

        // TODO: Initialize map here (Google Maps, Leaflet, etc.)
        // Example with Leaflet:
        /*
        document.addEventListener('DOMContentLoaded', function() {
            const map = L.map('tracking-map').setView([{{ $this->record->delivery_latitude ?? -1.2921 }}, {{ $this->record->delivery_longitude ?? 36.8219 }}], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add markers for pickup and delivery
            @if($this->record->pickup_latitude && $this->record->pickup_longitude)
            L.marker([{{ $this->record->pickup_latitude }}, {{ $this->record->pickup_longitude }}])
                .addTo(map)
                .bindPopup('Pickup Location');
            @endif

            @if($this->record->delivery_latitude && $this->record->delivery_longitude)
            L.marker([{{ $this->record->delivery_latitude }}, {{ $this->record->delivery_longitude }}])
                .addTo(map)
                .bindPopup('Delivery Location');
            @endif

            // Add latest tracking point
            @if($this->record->tracking->first())
            L.marker([{{ $this->record->tracking->first()->latitude }}, {{ $this->record->tracking->first()->longitude }}], {
                icon: L.icon({
                    iconUrl: '/path/to/rider-icon.png',
                    iconSize: [32, 32]
                })
            })
            .addTo(map)
            .bindPopup('Current Location');
            @endif
        });
        */
    </script>
    @endif
</x-filament-panels::page>