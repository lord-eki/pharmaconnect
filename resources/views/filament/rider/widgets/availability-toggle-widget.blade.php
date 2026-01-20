<x-filament-widgets::widget>
    @php
        $status = $this->getAvailabilityStatus();
        $isAvailable = $status['is_available'];
        $isActive = $status['is_active'];
    @endphp

    <x-filament::section>
        <div class="flex items-center justify-between p-4 rounded-lg {{ $isAvailable ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-800' }}">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <div class="w-16 h-16 rounded-full {{ $isAvailable ? 'bg-green-500' : 'bg-gray-400' }} flex items-center justify-center">
                        @if($isAvailable)
                            <x-heroicon-o-check-circle class="w-10 h-10 text-white" />
                        @else
                            <x-heroicon-o-x-circle class="w-10 h-10 text-white" />
                        @endif
                    </div>
                    @if($isAvailable)
                        <span class="absolute top-0 right-0 w-5 h-5 bg-green-500 rounded-full animate-ping"></span>
                        <span class="absolute top-0 right-0 w-5 h-5 bg-green-500 rounded-full"></span>
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-bold {{ $isAvailable ? 'text-green-700 dark:text-green-400' : 'text-gray-700 dark:text-gray-400' }}">
                        {{ $isAvailable ? 'You are Available' : 'You are Unavailable' }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $isAvailable 
                            ? 'Ready to accept new deliveries' 
                            : 'Not accepting new deliveries' 
                        }}
                    </p>
                    @if(!$isActive)
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                            ⚠️ Your account is inactive. Contact support.
                        </p>
                    @endif
                </div>
            </div>

            @if($isActive)
                <div>
                    <x-filament::button
                        wire:click="toggleAvailability"
                        :color="$isAvailable ? 'danger' : 'success'"
                        size="lg"
                    >
                        {{ $isAvailable ? 'Go Offline' : 'Go Online' }}
                    </x-filament::button>
                </div>
            @endif
        </div>

        {{-- Quick Stats Below Toggle --}}
        <div class="grid grid-cols-3 gap-4 mt-4">
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ auth()->user()->rider->total_deliveries ?? 0 }}
                </p>
                <p class="text-xs text-gray-500">Total Deliveries</p>
            </div>

            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-2xl font-bold text-yellow-600">
                    {{ number_format(auth()->user()->rider->rating ?? 0, 1) }} ★
                </p>
                <p class="text-xs text-gray-500">Your Rating</p>
            </div>

            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-2xl font-bold text-blue-600">
                    {{ auth()->user()->rider->deliveries()->whereIn('status', ['assigned', 'picked_up'])->count() }}
                </p>
                <p class="text-xs text-gray-500">Active Now</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>