<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class CommissionStatementService
{
    /**
     * Generate a commission statement PDF and return it as a streamed download.
     */
    public function download(User $physician, ?int $year = null, ?int $month = null): Response
    {
        $year  = $year  ?? now()->year;
        $month = $month ?? null; // null = all months in the year

        $query = Commission::with(['prescription.patient', 'order'])
            ->where('physician_id', $physician->id)
            ->whereYear('created_at', $year)
            ->orderBy('created_at');

        if ($month) {
            $query->whereMonth('created_at', $month);
        }

        $commissions = $query->get();

        // Summary figures
        $totalGross      = $commissions->sum('gross_amount');
        $totalCommission = $commissions->sum('commission_amount');
        $totalPaid       = $commissions->where('status', 'paid')->sum('commission_amount');
        $totalApproved   = $commissions->where('status', 'approved')->sum('commission_amount');
        $totalPending    = $commissions->where('status', 'pending')->sum('commission_amount');

        // Group by month for the monthly breakdown table
        $byMonth = $commissions->groupBy(fn ($c) => $c->created_at->format('F Y'));

        $periodLabel = $month
            ? now()->setYear($year)->setMonth($month)->format('F Y')
            : "Full Year {$year}";

        $pdf = Pdf::loadView('pdf.commission-statement', [
            'physician'       => $physician,
            'commissions'     => $commissions,
            'byMonth'         => $byMonth,
            'year'            => $year,
            'month'           => $month,
            'totalGross'      => $totalGross,
            'totalCommission' => $totalCommission,
            'totalPaid'       => $totalPaid,
            'totalApproved'   => $totalApproved,
            'totalPending'    => $totalPending,
            'generated_at'    => now(),
            'period_label'    => $periodLabel,
        ])
        ->setPaper('a4')
        ->setOption('margin-top', 0)
        ->setOption('margin-bottom', 0)
        ->setOption('margin-left', 0)
        ->setOption('margin-right', 0);

        $fileName = sprintf(
            'commission-statement-%s-%s.pdf',
            str($physician->name)->slug(),
            $month
                ? now()->setYear($year)->setMonth($month)->format('Y-m')
                : $year
        );

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}