<div class="space-y-4">
    @if(empty($history))
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-gray-400">No rejection history available</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($history as $index => $rejection)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-sm font-semibold">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $rejection['rejected_supplier_name'] }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($rejection['rejected_at'])->format('M d, Y H:i') }}
                                </p>
                            </div>
                        </div>
                        
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                            Rejected
                        </span>
                    </div>

                    <div class="mt-3 space-y-2">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Rejection Reason</dt>
                            <dd class="text-sm text-gray-900 dark:text-white mt-0.5">
                                {{ $rejection['reason'] }}
                            </dd>
                        </div>

                        @if(isset($rejection['rejected_by']))
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Rejected By</dt>
                                <dd class="text-sm text-gray-900 dark:text-white mt-0.5">
                                    User ID: {{ $rejection['rejected_by'] }}
                                </dd>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Summary --}}
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ count($history) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Rejections</p>
                </div>
                
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $order->reassignment_count }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Reassignments</p>
                </div>

                <div>
                    <p class="text-2xl font-bold {{ $order->status === 'sent_to_supplier' ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Current Status</p>
                </div>
            </div>
        </div>

        {{-- Timeline Visualization --}}
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Order Journey</h4>
            <div class="relative">
                @foreach($history as $index => $rejection)
                    <div class="flex gap-3 mb-4">
                        <div class="flex flex-col items-center">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            @if($index < count($history) - 1)
                                <div class="w-0.5 h-full bg-gray-300 dark:bg-gray-600 flex-1 my-1"></div>
                            @endif
                        </div>
                        <div class="flex-1 pb-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                Rejected by {{ $rejection['rejected_supplier_name'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($rejection['rejected_at'])->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
                
                @if($order->status !== 'cancelled')
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-3 h-3 rounded-full {{ $order->status === 'sent_to_supplier' ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                @if($order->status === 'sent_to_supplier')
                                    Assigned to {{ $order->supplier->company_name }}
                                @else
                                    Awaiting Reassignment
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Current</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
