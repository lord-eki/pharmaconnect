<x-filament-widgets::widget>
    <x-filament::section
        heading="System Health"
        description="API health, payment processing, error monitoring"
        class="h-full"
    >
        <div class="space-y-3">
            @foreach($this->getHealthMetrics() as $metric)
                <div class="flex items-start justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full 
                                @if($metric['color'] === 'success') bg-green-500
                                @elseif($metric['color'] === 'warning') bg-yellow-500
                                @elseif($metric['color'] === 'danger') bg-red-500
                                @else bg-blue-500
                                @endif
                            "></span>
                            <h4 class="font-semibold text-sm text-gray-900 dark:text-gray-100">
                                {{ $metric['name'] }}
                            </h4>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $metric['description'] }}
                        </p>
                    </div>
                    @if($metric['uptime'])
                        <div class="text-right ml-4">
                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                {{ $metric['uptime'] }}
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">uptime</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>