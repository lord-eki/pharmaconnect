<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $activeDelivery = $this->getActiveDelivery();
            $pendingDeliveries = $this->getPendingDeliveries();
        @endphp

        @if($activeDelivery)
            {{-- Active Delivery Section --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Active Delivery</h3>
                    <x-filament::badge :color="match($activeDelivery->status) {
                        'assigned' => 'info',
                        'picked_up' => 'warning',
                        'in_transit' => 'primary',
                        default => 'gray'
                    }">
                        {{ ucfirst(str_replace('_', ' ', $activeDelivery->status)) }}
                    </x-filament::badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <div class="flex items-start gap-2">
                            <x-heroicon-o-hashtag class="w-5 h-5 text-gray-500 mt-0.5" />
                            <div>
                                <p class="text-sm text-gray-500">Delivery Number</p>
                                <p class="font-semibold">{{ $activeDelivery->delivery_number }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <x-heroicon-o-user class="w-5 h-5 text-gray-500 mt-0.5" />
                            <div>
                                <p class="text-sm text-gray-500">Recipient</p>
                                <p class="font-semibold">{{ $activeDelivery->recipient_name }}</p>
                                <p class="text-sm text-gray-600">{{ $activeDelivery->recipient_phone }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-start gap-2">
                            <x-heroicon-o-map-pin class="w-5 h-5 text-gray-500 mt-0.5" />
                            <div>
                                <p class="text-sm text-gray-500">Delivery Address</p>
                                <p class="font-semibold">{{ $activeDelivery->delivery_address }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <x-heroicon-o-clock class="w-5 h-5 text-gray-500 mt-0.5" />
                            <div>
                                <p class="text-sm text-gray-500">Estimated Delivery</p>
                                <p class="font-semibold">{{ $activeDelivery->estimated_delivery?->format('M j, Y g:i A') ?? 'Not set' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($activeDelivery->delivery_notes)
                    <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                        <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">Notes:</p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ $activeDelivery->delivery_notes }}</p>
                    </div>
                @endif
            </div>
        @else
            {{-- No Active Delivery - Show Pending Deliveries --}}
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Available Deliveries</h3>
                    <x-filament::badge color="info">
                        {{ $pendingDeliveries->count() }} Available
                    </x-filament::badge>
                </div>

                @if($pendingDeliveries->count() > 0)
                    <div class="space-y-3">
                        @foreach($pendingDeliveries as $delivery)
                            <div class="p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="font-semibold">{{ $delivery->delivery_number }}</span>
                                            <x-filament::badge color="warning">Pending</x-filament::badge>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-user class="w-4 h-4 inline" />
                                            {{ $delivery->recipient_name }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-map-pin class="w-4 h-4 inline" />
                                            {{ Str::limit($delivery->delivery_address, 50) }}
                                        </p>
                                        @if($delivery->estimated_distance_km)
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                <x-heroicon-o-arrow-path class="w-4 h-4 inline" />
                                                {{ number_format($delivery->estimated_distance_km, 1) }} km
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="text-center pt-2">
                        <x-filament::button 
                            tag="a" 
                            href="{{ route('filament.rider.resources.deliveries.index') }}"
                            color="primary"
                        >
                            View All Deliveries
                        </x-filament::button>
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-heroicon-o-inbox class="w-16 h-16 mx-auto text-gray-400" />
                        <p class="mt-2 text-sm text-gray-500">No deliveries available in your area</p>
                    </div>
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>