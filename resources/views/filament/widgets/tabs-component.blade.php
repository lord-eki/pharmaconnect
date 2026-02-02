<x-filament-widgets::widget>
    <div class="w-full">
        <div class="flex justify-center mb-6">
            <x-filament::tabs label="Management Sections" class="w-full max-w-4xl">
                @foreach($this->getTabContent() as $key => $tab)
                    @php
                        $icon = match($key) {
                            'users' => 'heroicon-o-user-group',
                            'financial' => 'heroicon-o-currency-dollar',
                            'pricing' => 'heroicon-o-adjustments-vertical',
                            'system' => 'heroicon-o-cog',
                            default => 'heroicon-o-square-3-stack-3d'
                        };
                    @endphp
                    
                    <x-filament::tabs.item 
                        :active="$activeTab === $key"
                        wire:click="setActiveTab('{{ $key }}')"
                        :icon="$icon"
                        class="flex-1 text-center cursor-pointer"
                    >
                        {{ $tab['title'] }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>

        {{-- Tab Content Area --}}
        <div class="mt-6">
            <x-filament::section>
                @foreach($this->getTabContent() as $key => $tab)
                    @if($activeTab === $key)
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {{ $tab['title'] }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $tab['content'] }}
                            </p>
                        </div>
                    @endif
                @endforeach
            </x-filament::section>
        </div>
    </div>
</x-filament-widgets::widget>