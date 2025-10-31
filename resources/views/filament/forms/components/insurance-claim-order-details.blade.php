
@php
    if (!$prescription_id) {
        echo '<p class="text-gray-500">No prescription selected</p>';
        return;
    }

    $prescription = \App\Models\Prescription::with([
        'orders' => fn($q) => $q->with(['supplier', 'items.medicine'])
    ])->find($prescription_id);

    if (!$prescription || $prescription->orders->isEmpty()) {
        echo '<p class="text-gray-500">No orders found for this prescription</p>';
        return;
    }
@endphp

<div class="space-y-4">
    @foreach($prescription->orders as $order)
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="font-semibold text-gray-900">{{ $order->order_number }}</h4>
                    <p class="text-sm text-gray-600">Supplier: {{ $order->supplier->name ?? 'Unknown' }}</p>
                    <p class="text-sm text-gray-600">Status: 
                        <span class="px-2 py-1 rounded text-xs font-medium
                            @if($order->status === 'delivered') bg-green-100 text-green-800
                            @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($order->status === 'confirmed') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-gray-900">KES {{ number_format($order->total_amount, 2) }}</p>
                    <p class="text-xs text-gray-500">{{ $order->ordered_at?->format('M d, Y') }}</p>
                </div>
            </div>

            @if($order->items->isNotEmpty())
                <div class="mt-3 border-t border-gray-200 pt-3">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">Order Items:</h5>
                    <div class="space-y-2">
                        @foreach($order->items as $item)
                            <div class="flex justify-between text-sm">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ $item->medicine->generic_name ?? 'N/A' }}</p>
                                    <p class="text-gray-600">
                                        Qty: {{ $item->quantity }} × KES {{ number_format($item->unit_price, 2) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-gray-900">KES {{ number_format($item->total_price, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach

    <div class="border-t-2 border-gray-300 pt-3 mt-4">
        <div class="flex justify-between items-center">
            <span class="text-base font-semibold text-gray-900">Total Claimed Amount:</span>
            <span class="text-xl font-bold text-gray-900">
                KES {{ number_format($prescription->orders->sum('total_amount'), 2) }}
            </span>
        </div>
    </div>
</div>