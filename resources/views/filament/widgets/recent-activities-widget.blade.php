<x-filament-widgets::widget>
    <x-filament::section
        heading="Recent Activities"
        description="Latest user actions, system events, alerts"
        class="h-full"
    >
        <div class="space-y-3">
            @foreach($this->getActivities() as $activity)
                <div class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center
                            @if($activity['color'] === 'success') bg-green-100 dark:bg-green-900/30
                            @elseif($activity['color'] === 'info') bg-blue-100 dark:bg-blue-900/30
                            @elseif($activity['color'] === 'warning') bg-yellow-100 dark:bg-yellow-900/30
                            @elseif($activity['color'] === 'danger') bg-red-100 dark:bg-red-900/30
                            @endif
                        ">
                            <x-filament::icon
                                :icon="$activity['icon']"
                                class="w-4 h-4
                                    @if($activity['color'] === 'success') text-green-600 dark:text-green-400
                                    @elseif($activity['color'] === 'info') text-blue-600 dark:text-blue-400
                                    @elseif($activity['color'] === 'warning') text-yellow-600 dark:text-yellow-400
                                    @elseif($activity['color'] === 'danger') text-red-600 dark:text-red-400
                                    @endif
                                "
                            />
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-1">
                            {{ $activity['title'] }}
                        </h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                            {{ $activity['description'] }}
                        </p>
                        <span class="text-xs text-gray-500 dark:text-gray-500">
                            {{ $activity['time'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>