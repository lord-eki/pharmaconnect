<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupplierFinancialsReportService;

class SupplierFinancialsReportController extends Controller
{

public function  __construct(protected SupplierFinancialsReportService $service)
{
    
}

public function download(Request $request){
$request->validate([
    'year' => ['required','integer','min:2000','max:'.now()->year],
    'month' => ['nullable','integer','between:1,12'],
]);

return $this->service->download($request->integer('year') ?: null, $request->integer('month') ?: null);
    }
}
