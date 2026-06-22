<?php

namespace App\Services;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

class SupplierFinancialsReportService
{
   

    public function download(?int $year, ?int $month)
    {
       $year = $year  ?? now()->year;
       $month = $month ?? null; 
       $supplier = Auth::user()->supplier;

       $query = Payment::where('status','completed')
       ->where('payee_id', $supplier->id)->whereYear('created_at', $year)
       ->orderBy('created_at');

    if ($month) {
        $query->whereMonth('created_at', $month);
    }

    $payments = $query->get();

       $totalAmount = $payments->sum('amount');

       $byMonth = $payments->groupBy(fn ($p) => $p->created_at->format('F Y'));

       $periodLabel = $month
           ? now()->setYear($year)->setMonth($month)->format('F Y')
           : "Full Year {$year}";

       $pdf = Pdf::loadView('pdf.supplier-financials-report', [
           'supplier' => $supplier,
           'payments' => $payments,
           'byMonth' => $byMonth,
           'year' => $year,
           'month' => $month,
           'totalAmount' => $totalAmount,
           'generated_at' => now(),
           'period_label' => $periodLabel,
       ])
       ->setPaper('a4')
       ->setOption('margin-top', 0);

       return response()->streamDownload(
           fn () => print($pdf->output()),
           "Supplier_Financials_Report_{$periodLabel}.pdf"
       );
    }
}
