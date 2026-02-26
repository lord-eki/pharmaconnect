<div class="space-y-6 p-2">

    {{-- Meta Info --}}
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
            <h3 class="font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-xs">Event Info</h3>

            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Event</span>
                <span @class([
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $record->event === 'created',
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $record->event === 'updated',
                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $record->event === 'deleted',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $record->event === 'restored',
                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' => !in_array($record->event, ['created','updated','deleted','restored']),
                ])>
                    {{ ucfirst($record->event) }}
                </span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Model</span>
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ class_basename($record->auditable_type) }}</span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Record ID</span>
                <span class="font-mono font-medium text-gray-800 dark:text-gray-200">#{{ $record->auditable_id }}</span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Date & Time</span>
                <span class="text-gray-800 dark:text-gray-200">{{ $record->created_at->format('M d, Y H:i:s') }}</span>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
            <h3 class="font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-xs">Actor & Context</h3>

            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">User</span>
                <span class="font-medium text-gray-800 dark:text-gray-200">
                    {{ $record->user?->name ?? 'System' }}
                    @if($record->user)
                        <span class="text-xs text-gray-400">(#{{ $record->user_id }})</span>
                    @endif
                </span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">IP Address</span>
                <span class="font-mono text-gray-800 dark:text-gray-200">{{ $record->ip_address ?? '—' }}</span>
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-gray-500 dark:text-gray-400">URL</span>
                <span class="font-mono text-xs text-gray-700 dark:text-gray-300 break-all">{{ $record->url ?? '—' }}</span>
            </div>

            @if($record->tags)
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Tags</span>
                <span class="text-gray-800 dark:text-gray-200">{{ $record->tags }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Changes --}}
    @if($record->event === 'updated' && ($record->old_values || $record->new_values))
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Changes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50">
                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-1/4">Field</th>
                        <th class="text-left px-4 py-2 text-xs font-semibold text-red-500 uppercase tracking-wide w-[37.5%]">Old Value</th>
                        <th class="text-left px-4 py-2 text-xs font-semibold text-green-500 uppercase tracking-wide w-[37.5%]">New Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @php
                        $oldValues = $record->old_values ?? [];
                        $newValues = $record->new_values ?? [];
                        $allKeys = collect(array_merge(array_keys($oldValues), array_keys($newValues)))->unique();
                    @endphp
                    @foreach($allKeys as $key)
                    @php
                        $old = $oldValues[$key] ?? null;
                        $new = $newValues[$key] ?? null;
                        $changed = $old !== $new;
                    @endphp
                    <tr class="{{ $changed ? 'bg-yellow-50/50 dark:bg-yellow-900/10' : '' }}">
                        <td class="px-4 py-2 font-mono text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ str_replace('_', ' ', ucwords($key, '_')) }}
                        </td>
                        <td class="px-4 py-2">
                            @if(is_null($old))
                                <span class="text-gray-400 italic text-xs">null</span>
                            @elseif(is_array($old))
                                <pre class="text-xs text-red-600 dark:text-red-400 whitespace-pre-wrap break-all">{{ json_encode($old, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <span class="text-xs text-red-600 dark:text-red-400 break-all">{{ $old }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if(is_null($new))
                                <span class="text-gray-400 italic text-xs">null</span>
                            @elseif(is_array($new))
                                <pre class="text-xs text-green-600 dark:text-green-400 whitespace-pre-wrap break-all">{{ json_encode($new, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <span class="text-xs text-green-600 dark:text-green-400 break-all">{{ $new }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Created: show new_values --}}
    @elseif($record->event === 'created' && $record->new_values)
    <div class="rounded-lg border border-green-200 dark:border-green-800 overflow-hidden">
        <div class="bg-green-50 dark:bg-green-900/20 px-4 py-2 border-b border-green-200 dark:border-green-800">
            <h3 class="font-semibold text-green-700 dark:text-green-300 text-sm">Created With</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-green-50/50 dark:bg-green-900/10">
                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-1/3">Field</th>
                        <th class="text-left px-4 py-2 text-xs font-semibold text-green-600 uppercase tracking-wide">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($record->new_values as $key => $value)
                    <tr>
                        <td class="px-4 py-2 font-mono text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ str_replace('_', ' ', ucwords($key, '_')) }}
                        </td>
                        <td class="px-4 py-2">
                            @if(is_null($value))
                                <span class="text-gray-400 italic text-xs">null</span>
                            @elseif(is_array($value))
                                <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-all">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <span class="text-xs text-gray-700 dark:text-gray-300 break-all">{{ $value }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Deleted: show old_values --}}
    @elseif($record->event === 'deleted' && $record->old_values)
    <div class="rounded-lg border border-red-200 dark:border-red-800 overflow-hidden">
        <div class="bg-red-50 dark:bg-red-900/20 px-4 py-2 border-b border-red-200 dark:border-red-800">
            <h3 class="font-semibold text-red-700 dark:text-red-300 text-sm">Deleted Record Snapshot</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-red-50/50 dark:bg-red-900/10">
                        <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-1/3">Field</th>
                        <th class="text-left px-4 py-2 text-xs font-semibold text-red-600 uppercase tracking-wide">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($record->old_values as $key => $value)
                    <tr>
                        <td class="px-4 py-2 font-mono text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ str_replace('_', ' ', ucwords($key, '_')) }}
                        </td>
                        <td class="px-4 py-2">
                            @if(is_null($value))
                                <span class="text-gray-400 italic text-xs">null</span>
                            @elseif(is_array($value))
                                <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-all">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <span class="text-xs text-gray-700 dark:text-gray-300 break-all">{{ $value }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- User Agent --}}
    @if($record->user_agent)
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="font-semibold text-gray-700 dark:text-gray-300 text-xs uppercase tracking-wide mb-2">User Agent</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 break-all font-mono">{{ $record->user_agent }}</p>
    </div>
    @endif

</div>