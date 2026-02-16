
<div class="space-y-4">
    {{-- Current Order Info --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
            Current Order Information
        </h3>
        
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Order Number</dt>
                <dd class="font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</dd>
            </div>
            
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Current Total</dt>
                <dd class="font-medium text-gray-900 dark:text-white">
                    KES {{ number_format($order->total_amount, 2) }}
                </dd>
            </div>

            @if($order->prescription)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Prescription</dt>
                <dd class="font-medium text-gray-900 dark:text-white">
                    {{ $order->prescription->prescription_number }}
                </dd>
            </div>
            @endif

            <div>
                <dt class="text-gray-500 dark:text-gray-400">Rejection Count</dt>
                <dd class="font-medium text-gray-900 dark:text-white">
                    {{ $options['rejection_count'] }} time(s)
                </dd>
            </div>
        </dl>
    </div>

    {{-- Rejected Supplier Info --}}
    <div class="rounded-lg border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/10 p-4">
        <h3 class="text-sm font-semibold text-red-900 dark:text-red-300 mb-3">
            Rejected Supplier
        </h3>
        
        <dl class="space-y-2 text-sm">
            <div>
                <dt class="text-red-700 dark:text-red-400">Supplier Name</dt>
                <dd class="font-medium text-red-900 dark:text-red-200">
                    {{ $options['current_supplier']['name'] }}
                </dd>
            </div>
            
            <div>
                <dt class="text-red-700 dark:text-red-400">Original Total</dt>
                <dd class="font-medium text-red-900 dark:text-red-200">
                    KES {{ number_format($options['current_supplier']['total'], 2) }}
                </dd>
            </div>

            @if($order->rejection_reason)
            <div>
                <dt class="text-red-700 dark:text-red-400">Rejection Reason</dt>
                <dd class="font-medium text-red-900 dark:text-red-200">
                    {{ $order->rejection_reason }}
                </dd>
            </div>
            @endif

            @if($order->rejected_at)
            <div>
                <dt class="text-red-700 dark:text-red-400">Rejected At</dt>
                <dd class="font-medium text-red-900 dark:text-red-200">
                    {{ $order->rejected_at->format('M d, Y H:i') }}
                </dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Recommended Supplier --}}
    @if(!empty($options['recommended']))
    <div class="rounded-lg border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/10 p-4">
        <h3 class="text-sm font-semibold text-green-900 dark:text-green-300 mb-3 flex items-center gap-2">
            <span>⭐</span>
            <span>Recommended Supplier (Cheapest)</span>
        </h3>
        
        <dl class="space-y-2 text-sm">
            <div>
                <dt class="text-green-700 dark:text-green-400">Supplier Name</dt>
                <dd class="font-medium text-green-900 dark:text-green-200">
                    {{ $options['recommended']['supplier_name'] }}
                </dd>
            </div>
            
            <div>
                <dt class="text-green-700 dark:text-green-400">New Total</dt>
                <dd class="font-medium text-green-900 dark:text-green-200">
                    KES {{ number_format($options['recommended']['total_cost'], 2) }}
                </dd>
            </div>

            <div>
                <dt class="text-green-700 dark:text-green-400">Price Difference</dt>
                <dd class="font-medium text-green-900 dark:text-green-200">
                    @php
                        $diff = $options['recommended']['total_cost'] - $options['current_supplier']['total'];
                        $color = $diff > 0 ? 'text-red-600' : 'text-green-600';
                        $sign = $diff > 0 ? '+' : '';
                    @endphp
                    <span class="{{ $color }}">
                        {{ $sign }}KES {{ number_format($diff, 2) }}
                    </span>
                </dd>
            </div>
        </dl>
    </div>
    @else
    <div class="rounded-lg border border-yellow-200 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/10 p-4">
        <p class="text-sm text-yellow-800 dark:text-yellow-300">
            ⚠️ No recommended supplier found. Please select manually from available options.
        </p>
    </div>
    @endif

    {{-- Order Items --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
            Order Items
        </h3>
        
        <div class="space-y-2">
            @foreach($order->items as $item)
            <div class="flex justify-between items-center text-sm border-b border-gray-100 dark:border-gray-700 pb-2">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $item->medicine->generic_name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Quantity: {{ $item->quantity }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-medium text-gray-900 dark:text-white">
                        KES {{ number_format($item->total_price, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @ KES {{ number_format($item->unit_price, 2) }}/unit
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Available Suppliers Count --}}
    <div class="rounded-lg border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/10 p-4">
        <p class="text-sm text-blue-900 dark:text-blue-300">
            <strong>{{ count($options['all_options']) }}</strong> alternative supplier(s) available
        </p>
    </div>
</div>