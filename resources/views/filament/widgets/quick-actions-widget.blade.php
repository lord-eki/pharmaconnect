<x-filament-widgets::widget>
    <x-filament::section
        heading="Quick Actions"
        description="Quick access to admin functions"
        class="h-full"
    >
        <div class="grid grid-cols-1 gap-3">
            @foreach ($this->getActions() as $action)
                <div class="w-full">
                    {{ $action }}
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>