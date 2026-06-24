<x-filament-panels::page>

    <div class="flex justify-end mb-2">
        <x-filament::button
            wire:click="refresh"
            wire:loading.attr="disabled"
            color="gray"
            size="sm"
            icon="heroicon-o-arrow-path"
        >
            <span wire:loading.remove wire:target="refresh">Refresh Data</span>
            <span wire:loading wire:target="refresh">Refreshing…</span>
        </x-filament::button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach($stats as $stat)
            <x-filament::section>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-1">
                        <div class="text-3xl font-bold tracking-tight">
                            {{ $stat['value'] }}
                        </div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $stat['label'] }}
                        </div>
                        @if($stat['description'] ?? null)
                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $stat['description'] }}
                            </div>
                        @endif
                    </div>
                    @if($stat['icon'] ?? null)
                        <div class="text-gray-300 dark:text-gray-600 mt-1">
                            <x-filament::icon
                                :icon="$stat['icon']"
                                class="w-8 h-8"
                            />
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <x-filament::section>
                <x-slot name="heading">Claims by Status</x-slot>

                <div class="relative" style="height: 200px;">
                    <div id="status-skeleton" 
                        class="absolute inset-0 flex items-center justify-center animate-pulse z-10">
                        <div class="w-40 h-40 rounded-full bg-gray-100 dark:bg-gray-800"></div>
                    </div>
                    <canvas id="statusChart"></canvas> 
                </div>
            </x-filament::section>

        <x-filament::section height="200px">
            <x-slot name="heading">Monthly Trend ({{ now()->year }})</x-slot>

             <div
                id="trend-skeleton"
                class="h-64 animate-pulse rounded bg-gray-100 dark:bg-gray-800"
            ></div>

            <canvas id="trendChart" class="hidden" style="max-height:200px;"></canvas>
        </x-filament::section>

        <x-filament::section class="lg:col-span-2">
            <x-slot name="heading">Top 10 Medicines by Frequency</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class=" border border-gray-300 dark:border-gray-700 text-left">
                            <th class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">#</th>
                            <th class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Medicine</th>
                            <th class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-400 text-right">Frequency</th>
                            <th class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-400 text-right">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($topMedicines as $i => $medicine)
                            <tr class="hover:bg-gray-50 border border-gray-300   dark:hover:bg-white/5 transition-colors">
                                <td class="py-2 px-3 text-gray-400 dark:text-gray-600 font-mono text-xs">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="py-2 px-3">
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ $medicine['generic_name'] }}
                                    </span>
                                    @if($medicine['brand_name'] ?? null)
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                                            {{ $medicine['brand_name'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-right">
                                    <span class="inline-flex items-center justify-center rounded-full bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-400 text-xs font-semibold px-2.5 py-0.5">
                                        {{ number_format($medicine['frequency']) }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-right font-medium text-gray-900 dark:text-white">
                                    KES {{ number_format($medicine['total_cost'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-400 dark:text-gray-600">
                                    No medicine data available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

    </div>


    <script>
        window.__claimsStatus = @json($statusData);
        window.__claimsTrend  = @json($trendData);
    </script>

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"
        defer
        onload="window.__initClaimsCharts && window.__initClaimsCharts()"
    ></script>

    <script>
        window.__initClaimsCharts = function () {
            const statusData = window.__claimsStatus;
            const trendData  = window.__claimsTrend;

            const statusCanvas = document.getElementById('statusChart');
            const statusSkeleton = document.getElementById('status-skeleton');

            if (statusCanvas && Object.keys(statusData).length) {
                const statusLabels = Object.keys(statusData).map(
                    s => s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
                );

                new Chart(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: Object.values(statusData),
                            backgroundColor: [
                                '#94a3b8', 
                                '#60a5fa', 
                                '#34d399', 
                                '#f87171', 
                                '#f97316', 
                            ],
                            borderWidth: 2,
                            borderColor: 'transparent',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { padding: 16, font: { size: 11 } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ` ${ctx.label}: ${ctx.parsed} claims`
                                }
                            }
                        }
                    }
                });

                statusSkeleton.classList.add('hidden');
            }

            const trendCanvas   = document.getElementById('trendChart');
            const trendSkeleton = document.getElementById('trend-skeleton');

            if (trendCanvas && trendData.labels?.length) {
                new Chart(trendCanvas, {
                    type: 'line',
                    data: {
                        labels: trendData.labels,
                        datasets: [
                            {
                                label: 'Claimed (Ksh)',
                                data: trendData.claimed,
                                borderColor: '#f97316',
                                backgroundColor: 'rgba(249,115,22,0.08)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'Approved (Ksh)',
                                data: trendData.approved,
                                borderColor: '#34d399',
                                backgroundColor: 'rgba(52,211,153,0.08)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { font: { size: 11 }, padding: 16 }
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx =>
                                        ` ${ctx.dataset.label}: Ksh ${Number(ctx.parsed.y).toLocaleString()}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: v => 'Ksh ' + Number(v).toLocaleString()
                                },
                                grid: { color: 'rgba(0,0,0,0.04)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });

                trendSkeleton.classList.add('hidden');
            }
        };

        if (typeof Chart !== 'undefined') {
            window.__initClaimsCharts();
        }
    </script>

</x-filament-panels::page>