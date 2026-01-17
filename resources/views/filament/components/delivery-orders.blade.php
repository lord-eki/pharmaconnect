<div class="space-y-4">
    {{-- Summary Card --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Orders</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $orders->count() }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Orders Total</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    KES {{ number_format($orders->sum('total_amount'), 2) }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Delivery Fee</p>
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                    KES {{ number_format($delivery->delivery_fee, 2) }}
                </p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Grand Total</p>
                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                    KES {{ number_format($orders->sum('total_amount') + $delivery->delivery_fee, 2) }}
                </p>
            </div>
        </div>
    </div>

    {{-- Orders List --}}
    @foreach($orders as $index => $order)
        @php
            $pivot = $delivery->orders->find($order->id)?->pivot;
        @endphp
        
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Order Header --}}
            <div class="bg-primary-50 dark:bg-primary-900/20 px-4 py-3 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-600 text-white text-sm font-bold">
                        {{ $index + 1 }}
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $order->order_number }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $order->supplier->company_name }}</p>
                    </div>
                    <x-filament::badge :color="match($order->status) {
                        'pending_review' => 'warning',
                        'sent_to_supplier' => 'info',
                        'confirmed' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray'
                    }">
                        {{ str_replace('_', ' ', ucfirst($order->status)) }}
                    </x-filament::badge>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                    KES {{ number_format($order->total_amount, 2) }}
                </p>
            </div>

            {{-- Supplier & Pickup Info --}}
            <div class="grid md:grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                <div class="space-y-2">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Supplier</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $order->supplier->company_name }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pickup Address</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $order->supplier->address ?? 'No address' }}</p>
                        </div>
                    </div>
                    @if($order->supplier->phone)
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Contact</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $order->supplier->phone }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                @if($pivot)
                    <div class="space-y-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pickup Status</p>
                            <x-filament::badge :color="match($pivot->pickup_status) {
                                'picked_up' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                default => 'gray'
                            }" size="lg">
                                {{ ucfirst($pivot->pickup_status) }}
                            </x-filament::badge>
                        </div>
                        @if($pivot->picked_up_at)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Picked Up At</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $pivot->picked_up_at }}
                                </p>
                            </div>
                        @endif
                        @if($pivot->pickup_notes)
                            <div class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded border border-yellow-200 dark:border-yellow-800">
                                <p class="text-xs font-medium text-yellow-800 dark:text-yellow-200">Pickup Notes:</p>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ $pivot->pickup_notes }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Order Items --}}
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Items ({{ $order->items->count() }})
                    </h4>
                </div>
                
                <div class="space-y-2">
                    @foreach($order->items as $item)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $item->medicine->generic_name }}
                                </p>
                                @if($item->medicine->brand_name)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $item->medicine->brand_name }}
                                    </p>
                                @endif
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $item->medicine->strength }} • {{ $item->medicine->dosage_form }}
                                </p>
                            </div>
                            <div class="text-right ml-4">
                                <div class="inline-flex items-center gap-2 mb-1">
                                    <x-filament::badge color="info">
                                        Qty: {{ $item->quantity }}
                                    </x-filament::badge>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @ KES {{ number_format($item->unit_price, 2) }}
                                </p>
                                <p class="text-sm font-bold text-primary-600 dark:text-primary-400">
                                    KES {{ number_format($item->total_price, 2) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Order Total --}}
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <span class="font-semibold text-gray-900 dark:text-white">Order Total:</span>
                    <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                        KES {{ number_format($order->total_amount, 2) }}
                    </span>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Grand Total Summary --}}
    <div class="rounded-lg border-2 border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-900/20 p-4">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Delivery Summary</h4>
        <div class="space-y-2">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">Subtotal ({{ $orders->count() }} orders):</span>
                <span class="font-semibold text-gray-900 dark:text-white">
                    KES {{ number_format($orders->sum('total_amount'), 2) }}
                </span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">Delivery Fee:</span>
                <span class="font-semibold text-gray-900 dark:text-white">
                    KES {{ number_format($delivery->delivery_fee, 2) }}
                </span>
            </div>
            <div class="pt-2 border-t border-primary-200 dark:border-primary-800 flex justify-between items-center">
                <span class="text-base font-bold text-gray-900 dark:text-white">Grand Total:</span>
                <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                    KES {{ number_format($orders->sum('total_amount') + $delivery->delivery_fee, 2) }}
                </span>
            </div>
        </div>
    </div>
</div>