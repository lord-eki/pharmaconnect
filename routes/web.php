<?php

use App\Http\Controllers\InsuranceClaimController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth'])->group(function () {
    Route::get('/insurance-claims/{claim}/pdf', [
        InsuranceClaimController::class, 
        'viewPDF'
    ])->name('insurance-claims.pdf');
    
    Route::get('/insurance-claims/{claim}/download', [
        InsuranceClaimController::class, 
        'downloadPDF'
    ])->name('insurance-claims.download');
});