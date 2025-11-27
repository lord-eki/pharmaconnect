<div class="space-y-4">
    {{-- Patient Information --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-lg mb-2">Patient Information</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium">Name:</span> {{ $prescription->patient->full_name }}
            </div>
            <div>
                <span class="font-medium">Phone:</span> {{ $prescription->patient->phone }}
            </div>
            <div>
                <span class="font-medium">ID Number:</span> {{ $prescription->patient->id_number ?? 'N/A' }}
            </div>
            <div>
                <span class="font-medium">Policy #:</span> {{ $prescription->patient->insurance_number ?? 'N/A' }}
            </div>
        </div>
    </div>

    {{-- Prescription Details --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h3 class="font-semibold text-lg mb-2">Prescription Details</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium">Physician:</span> {{ $prescription->physician->name ?? 'N/A' }}
            </div>
            <div>
                <span class="font-medium">Date:</span> {{ $prescription->prescribed_at->format('M d, Y') }}
            </div>
            <div class="col-span-2">
                <span class="font-medium">Diagnosis:</span> {{ $prescription->diagnosis ?? 'N/A' }}
            </div>
            @if($prescription->notes)
            <div class="col-span-2">
                <span class="font-medium">Notes:</span> {{ $prescription->notes }}
            </div>
            @endif
        </div>
    </div>

    {{-- Medicines --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <h3 class="font-semibold text-lg p-4 bg-gray-100 border-b">Prescribed Medicines</h3>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dosage</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($prescription->items as $item)
                <tr>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $item->medicine->generic_name }}
                        </div>
                        @if($item->medicine->brand_name)
                        <div class="text-xs text-gray-500">
                            ({{ $item->medicine->brand_name }})
                        </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        {{ $item->dosage ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        {{ $item->quantity }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        KES {{ number_format($item->unit_price, 2) }}
                    </td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                        KES {{ number_format($item->total_price, 2) }}
                    </td>
                </tr>
                @endforeach
                <tr class="bg-gray-50">
                    <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-900">
                        Total:
                    </td>
                    <td class="px-4 py-3 font-bold text-gray-900">
                        KES {{ number_format($prescription->total_amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>