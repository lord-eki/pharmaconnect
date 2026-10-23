<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupplierFinancialsReportService;
use Carbon\Carbon;

class SupplierFinancialsReportController extends Controller
{

    public function  __construct(protected SupplierFinancialsReportService $service) {}

    public function download(Request $request)
    {
        $data = $request->validate([
            'from'  => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from', 'before_or_equal:today'],
        ]);

        $from  = Carbon::parse($data['from'])->startOfDay();
        $until = Carbon::parse($data['until'])->endOfDay();

        abort_if($from->diffInDays($until) > 92, 422, 'Please choose a period of 3 months or less.');

        return $this->service->download($from, $until);
    }
}
