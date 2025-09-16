<x-filament-widgets::widget>
    <x-filament::section
        heading="Recent Activities"
        description="Latest user actions, system events, alerts"
        class="h-full"
    >
        <div class="space-y-4">
            @foreach ($this->getActivities() as $activity)
                <div class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    <div class="flex-shrink-0">
                        @if ($activity['color'] === 'success')
                            <div class="w-3 h-3 bg-green-500 rounded-full mt-2"></div>
                        @elseif ($activity['color'] === 'info')
                            <div class="w-3 h-3 bg-blue-500 rounded-full mt-2"></div>
                        @elseif ($activity['color'] === 'warning')
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mt-2"></div>
                        @elseif ($activity['color'] === 'danger')
                            <div class="w-3 h-3 bg-red-500 rounded-full mt-2"></div>
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $activity['title'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $activity['description'] }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-xs text-gray-400 ml-2">
                                {{ $activity['time'] }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>