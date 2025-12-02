<?php

namespace App\Filament\Insurer\Pages;

use App\Models\InsuranceClaim;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use UnitEnum;

class ClaimsAnalytics extends Page
{
    protected string $view = 'filament.insurer.pages.claims-analytics';

     protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Claims Analytics';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';


     public function getStats(): array
    {
        $providerId = auth()->user()->insuranceProvider->id ?? 0;

        // This month stats
        $thisMonth = InsuranceClaim::where('insurance_provider_id', $providerId)
            ->whereMonth('submitted_at', now()->month)
            ->whereYear('submitted_at', now()->year);

        $totalClaimed = $thisMonth->sum('claimed_amount');
        $totalApproved = $thisMonth->sum('approved_amount');
        $totalClaims = $thisMonth->count();
        $approvedClaims = (clone $thisMonth)->where('status', 'approved')->count();
        
        $approvalRate = $totalClaims > 0 ? round(($approvedClaims / $totalClaims) * 100, 1) : 0;

        return [
            [
                'label' => 'Total Claims This Month',
                'value' => number_format($totalClaims),
                'description' => 'Submitted claims',
                'icon' => 'heroicon-o-document-text',
                'color' => 'info',
            ],
            [
                'label' => 'Total Claimed Amount',
                'value' => 'KES ' . number_format($totalClaimed, 2),
                'description' => 'This month',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'warning',
            ],
            [
                'label' => 'Total Approved Amount',
                'value' => 'KES ' . number_format($totalApproved, 2),
                'description' => 'This month',
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
            ],
            [
                'label' => 'Approval Rate',
                'value' => $approvalRate . '%',
                'description' => 'Claims approved',
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'primary',
            ],
        ];
    }

    public function getClaimsByStatus(): array
    {
        $providerId = auth()->user()->insuranceProvider->id ?? 0;

        return InsuranceClaim::where('insurance_provider_id', $providerId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getMonthlyTrend(): array
    {
        $providerId = auth()->user()->insuranceProvider->id ?? 0;

        $data = InsuranceClaim::where('insurance_provider_id', $providerId)
            ->whereYear('submitted_at', now()->year)
            ->select(
                DB::raw('MONTH(submitted_at) as month'),
                DB::raw('SUM(claimed_amount) as total_claimed'),
                DB::raw('SUM(approved_amount) as total_approved'),
                DB::raw('COUNT(*) as total_claims')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $result = [
            'labels' => [],
            'claimed' => [],
            'approved' => [],
            'claims' => [],
        ];

        foreach ($data as $item) {
            $result['labels'][] = $months[$item->month - 1];
            $result['claimed'][] = $item->total_claimed;
            $result['approved'][] = $item->total_approved;
            $result['claims'][] = $item->total_claims;
        }

        return $result;
    }

    public function getTopMedicines(): array
    {
        $providerId = auth()->user()->insuranceProvider->id ?? 0;

        return DB::table('insurance_claims')
            ->join('prescriptions', 'insurance_claims.prescription_id', '=', 'prescriptions.id')
            ->join('prescription_items', 'prescriptions.id', '=', 'prescription_items.prescription_id')
            ->join('medicines', 'prescription_items.medicine_id', '=', 'medicines.id')
            ->where('insurance_claims.insurance_provider_id', $providerId)
            ->select(
                'medicines.generic_name',
                'medicines.brand_name',
                DB::raw('COUNT(*) as frequency'),
                DB::raw('SUM(prescription_items.total_price) as total_cost')
            )
            ->groupBy('medicines.id', 'medicines.generic_name', 'medicines.brand_name')
            ->orderByDesc('frequency')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
