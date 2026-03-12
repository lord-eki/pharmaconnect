<?php

namespace App\Http\Controllers;

use App\Services\CommissionStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommissionStatementController extends Controller
{
    

public function __construct(protected CommissionStatementService $service)
{

}

public function download(Request $request)
{
    $request->validate([
        'year'  => ['required', 'integer', 'min:2000', 'max:' . now()->year],
        'month' => ['nullable', 'integer', 'between:1,12'],
    ]);

    return $this->service->download(Auth::user(),$request->integer('year') ?: null , $request->integer('month') ?: null);
}

}
