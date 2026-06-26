<div class="space-y-4">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-lg mb-2">{{ $medicine->display_name }}</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium">Category:</span> {{ $medicine->category->name ?? 'N/A' }}
            </div>
            <div>
                <span class="font-medium">Manufacturer:</span> {{ $medicine->manufacturer ?? 'N/A' }}
            </div>
            <div>
                <span class="font-medium">Pack Size:</span> {{ $medicine->pack_size ?? 'N/A' }}
            </div>
            <div>
                <span class="font-medium">PPB Reg #:</span> {{ $medicine->ppb_registration_number ?? 'N/A' }}
            </div>
        </div>
    </div>

    @if(isset($suppliers) && $suppliers->count() > 0)
  
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Supplier
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Unit Price
                        </th>
                      
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Expiry
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Batch #
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($suppliers as $supplier)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $supplier->supplier_name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $supplier->supplier_phone }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-green-600">
                                        KES {{ number_format($supplier->pricing['final_unit_price'], 2) }}
                                    </div>
                                </td>
                  
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($supplier->expiry_date)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $supplier->batch_number }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
       
    @endif

 
</div>