<div class="space-y-3 text-sm">
    @if ($pricing)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">
            <div class="flex justify-between">
                <span>Lowest supplier cost</span>
                <span>KES {{ number_format($pricing['supplier_price'], 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span>Mark-up ({{ $pricing['markup_percentage'] }}%)</span>
                <span>KES {{ number_format($pricing['markup_amount'], 2) }}</span>
            </div>
            <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold">
                <span>Selling price</span>
                <span>KES {{ number_format($pricing['final_unit_price'], 2) }}</span>
            </div>
        </div>
        <p class="text-xs text-gray-500">Cheapest supplier: {{ $cheapest->supplier_name }}</p>
    @else
        <p class="text-gray-500">No supplier currently has this medicine in stock.</p>
    @endif
</div>