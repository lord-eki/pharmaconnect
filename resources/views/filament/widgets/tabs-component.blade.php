<x-filament-widgets::widget>
    <div class="w-full">
        <div class="flex justify-center mb-6">
            <x-filament::tabs label="Management Sections" class="w-full max-w-4xl">
                <x-filament::tabs.item 
                    active 
                    icon="heroicon-o-user-group"
                    class="flex-1 text-center"
                >
                    Users Management
                </x-filament::tabs.item>

                <x-filament::tabs.item 
                    icon="heroicon-o-currency-dollar"
                    class="flex-1 text-center"
                >
                    Financial Management
                </x-filament::tabs.item>

                <x-filament::tabs.item 
                    icon="heroicon-o-adjustments-vertical"
                    class="flex-1 text-center"
                >
                    Pricing Configuration
                </x-filament::tabs.item>
                
                <x-filament::tabs.item 
                    icon="heroicon-o-cog"
                    class="flex-1 text-center"
                >
                    System Settings
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>

    </div>
</x-filament-widgets::widget>