<?php

namespace App\Filament\Insurer\Pages;

use App\Models\InsuranceClaim;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use UnitEnum;

class ClaimsAnalytics extends Page
{
    protected string $view = 'filament.insurer.pages.claims-analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Claims Analytics';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';


    public array  $stats        = [];
    public array  $statusData   = [];
    public array  $trendData    = [];
    public array  $topMedicines = [];


    public function mount(): void
    {
        $providerId = auth()->user()->insuranceProvider->id ?? 0;

        $ttl = now()->addMinutes(5);

        $this->stats = Cache::remember(
            "claims_analytics_stats_{$providerId}",
            $ttl,
            fn () => $this->buildStats($providerId)
        );

        $this->statusData = Cache::remember(
            "claims_analytics_status_{$providerId}",
            $ttl,
            fn () => $this->buildClaimsByStatus($providerId)
        );

        $this->trendData = Cache::remember(
            "claims_analytics_trend_{$providerId}",
            $ttl,
            fn () => $this->buildMonthlyTrend($providerId)
        );

        $this->topMedicines = Cache::remember(
            "claims_analytics_medicines_{$providerId}",
            $ttl,
            fn () => $this->buildTopMedicines($providerId)
        );
    }


    private function buildStats(int $providerId): array
    {
        $row = InsuranceClaim::where('insurance_provider_id', $providerId)
            ->whereMonth('submitted_at', now()->month)
            ->whereYear('submitted_at', now()->year)
            ->selectRaw('
                COUNT(*)                                          AS total_claims,
                SUM(claimed_amount)                               AS total_claimed,
                SUM(approved_amount)                              AS total_approved,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) AS approved_claims
            ')
            ->first();

        $totalClaims   = (int)   ($row->total_claims   ?? 0);
        $totalClaimed  = (float) ($row->total_claimed  ?? 0);
        $totalApproved = (float) ($row->total_approved ?? 0);
        $approvedCount = (int)   ($row->approved_claims ?? 0);
        $approvalRate  = $totalClaims > 0
            ? round(($approvedCount / $totalClaims) * 100, 1)
            : 0;

        return [
            [
                'label'       => 'Total Claims This Month',
                'value'       => number_format($totalClaims),
                'description' => 'Submitted claims',
                'icon'        => 'heroicon-o-document-text',
                'color'       => 'info',
            ],
            [
                'label'       => 'Total Claimed Amount',
                'value'       => 'KES ' . number_format($totalClaimed, 2),
                'description' => 'This month',
                'icon'        => 'heroicon-o-currency-dollar',
                'color'       => 'warning',
            ],
            [
                'label'       => 'Total Approved Amount',
                'value'       => 'KES ' . number_format($totalApproved, 2),
                'description' => 'This month',
                'icon'        => 'heroicon-o-check-circle',
                'color'       => 'success',
            ],
            [
                'label'       => 'Approval Rate',
                'value'       => $approvalRate . '%',
                'description' => 'Claims approved',
                'icon'        => 'heroicon-o-chart-bar',
                'color'       => 'primary',
            ],
        ];
    }

    private function buildClaimsByStatus(int $providerId): array
    {
        return InsuranceClaim::where('insurance_provider_id', $providerId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function buildMonthlyTrend(int $providerId): array
    {
        $months = [
            1 => 'January',    2 => 'February',  3 => 'March',
            4 => 'April',      5 => 'May',        6 => 'June',
            7 => 'July',       8 => 'August',     9 => 'September',
            10 => 'October',   11 => 'November',  12 => 'December',
        ];

        $rows = InsuranceClaim::where('insurance_provider_id', $providerId)
            ->whereYear('submitted_at', now()->year)
            ->selectRaw('
                MONTH(submitted_at)       AS month,
                SUM(claimed_amount)       AS total_claimed,
                SUM(approved_amount)      AS total_approved,
                COUNT(*)                  AS total_claims
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $result = ['labels' => [], 'claimed' => [], 'approved' => [], 'claims' => []];

        foreach ($rows as $row) {
            $result['labels'][]   = $months[$row->month];
            $result['claimed'][]  = (float) $row->total_claimed;
            $result['approved'][] = (float) $row->total_approved;
            $result['claims'][]   = (int)   $row->total_claims;
        }

        return $result;
    }

    private function buildTopMedicines(int $providerId): array
    {
        return DB::table('insurance_claims')
            ->join('prescriptions',      'insurance_claims.prescription_id',       '=', 'prescriptions.id')
            ->join('prescription_items', 'prescriptions.id',                        '=', 'prescription_items.prescription_id')
            ->join('medicines',          'prescription_items.medicine_id',          '=', 'medicines.id')
            ->where('insurance_claims.insurance_provider_id', $providerId)
            ->select(
                'medicines.generic_name',
                'medicines.brand_name',
                DB::raw('COUNT(*)                              AS frequency'),
                DB::raw('SUM(prescription_items.total_price)  AS total_cost')
            )
            ->groupBy('medicines.id', 'medicines.generic_name', 'medicines.brand_name')
            ->orderByDesc('frequency')
            ->limit(10)
            ->get()
            ->map(fn ($m) => (array) $m) 
            ->toArray();
    }


    public function refresh(): void
    {
        $providerId = auth()->user()->insuranceProvider->id ?? 0;

        Cache::forget("claims_analytics_stats_{$providerId}");
        Cache::forget("claims_analytics_status_{$providerId}");
        Cache::forget("claims_analytics_trend_{$providerId}");
        Cache::forget("claims_analytics_medicines_{$providerId}");

        $this->mount();
    }
}