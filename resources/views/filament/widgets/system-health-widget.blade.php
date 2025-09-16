<x-filament-widgets::widget>
    <x-filament::section
        heading="System Health"
        description="API health, payment processing, error monitoring"
        class="h-full"
    >
        <div class="space-y-4">
            @foreach ($this->getHealthMetrics() as $metric)
                <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        @if ($metric['color'] === 'success')
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        @elseif ($metric['color'] === 'warning')
                            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                        @elseif ($metric['color'] === 'info')
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        @elseif ($metric['color'] === 'danger')
                            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        @endif
                        
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $metric['name'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $metric['description'] }}
                            </p>
                        </div>
                    </div>
                    
                    @if ($metric['uptime'])
                        <div class="text-right">
                            @if ($metric['color'] === 'success')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ $metric['uptime'] }} uptime
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="text-right">
                            @if ($metric['color'] === 'success')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ ucfirst($metric['status']) }}
                                </span>
                            @elseif ($metric['color'] === 'warning')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    Degraded
                                </span>
                            @elseif ($metric['color'] === 'info')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    Monitoring
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>