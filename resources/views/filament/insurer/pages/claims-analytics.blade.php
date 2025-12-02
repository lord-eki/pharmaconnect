<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach($this->getStats() as $stat)
            <x-filament::section>
                <div class="flex flex-col gap-3">
                    <div class="text-3xl font-bold">
                        {{ $stat['value'] }}
                    </div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $stat['label'] }}
                    </div>
                    @if($stat['description'] ?? null)
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $stat['description'] }}
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Claims by Status -->
        <x-filament::section>
            <x-slot name="heading">
                Claims by Status
            </x-slot>
            <canvas id="statusChart"></canvas>
        </x-filament::section>

        <!-- Monthly Trend -->
        <x-filament::section>
            <x-slot name="heading">
                Monthly Trend
            </x-slot>
            <canvas id="trendChart"></canvas>
        </x-filament::section>

        <!-- Top Medicines -->
        <x-filament::section class="lg:col-span-2">
            <x-slot name="heading">
                Top 10 Medicines by Frequency
            </x-slot>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left p-2">Medicine</th>
                            <th class="text-right p-2">Frequency</th>
                            <th class="text-right p-2">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->getTopMedicines() as $medicine)
                        <tr class="border-b dark:border-gray-700">
                            <td class="p-2">
                                <strong>{{ $medicine->generic_name }}</strong>
                                @if($medicine->brand_name)
                                <br><span class="text-sm text-gray-500">({{ $medicine->brand_name }})</span>
                                @endif
                            </td>
                            <td class="text-right p-2">{{ $medicine->frequency }}</td>
                            <td class="text-right p-2">KES {{ number_format($medicine->total_cost, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Status Chart
        const statusData = @json($this->getClaimsByStatus());
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData).map(s => s.replace(/_/g, ' ').toUpperCase()),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: ['#fbbf24', '#60a5fa', '#34d399', '#ef4444', '#8b5cf6']
                }]
            }
        });

        // Trend Chart
        const trendData = @json($this->getMonthlyTrend());
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [
                    {
                        label: 'Claimed',
                        data: trendData.claimed,
                        borderColor: '#fbbf24',
                        tension: 0.4
                    },
                    {
                        label: 'Approved',
                        data: trendData.approved,
                        borderColor: '#34d399',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
    @endpush
</x-filament-panels::page>