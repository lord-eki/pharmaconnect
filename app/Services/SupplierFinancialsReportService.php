<?php

namespace App\Services;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SupplierFinancialsReportService
{
   

    public function download(Carbon $from, Carbon $until)
{
    $supplier = Auth::user()->supplier;

    $payments = Payment::where('status', 'completed')
        ->where('payee_id', $supplier->user_id)            // was $supplier->id
        ->whereBetween('created_at', [$from, $until])
        ->orderBy('created_at')
        ->get();

    $byMonth     = $payments->groupBy(fn ($p) => $p->created_at->format('F Y'));
    $periodLabel = $from->format('d M Y').' - '.$until->format('d M Y');

    $pdf = Pdf::loadView('pdf.supplier-financials-report', [
        'supplier'     => $supplier,
        'payments'     => $payments,
        'byMonth'      => $byMonth,
        'year'         => $from->year,
        'month'        => $from->isSameMonth($until) ? $from->month : null,
        'totalAmount'  => $payments->sum('amount'),
        'generated_at' => now(),
        'period_label' => $periodLabel,
    ])->setPaper('a4')->setOption('margin-top', 0);

    return response()->streamDownload(
        fn () => print($pdf->output()),
        'Supplier_Financials_'.$from->format('Ymd').'-'.$until->format('Ymd').'.pdf'
    );
}
}
