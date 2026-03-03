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

    Route::get('/documents/{document}/preview', function (App\Models\Document $document) {
        $fullPath = storage_path('app/private/' . $document->file_path);

        abort_if(! file_exists($fullPath), 404, 'File not found.');

        return response()->file($fullPath, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
        ]);
    })->name('documents.preview');

    Route::get('/documents/{document}/download', function (App\Models\Document $document) {
        $fullPath = storage_path('app/private/' . $document->file_path);

        abort_if(! file_exists($fullPath), 404, 'File not found.');

        $document->logAccess(auth()->user(), 'download');

        return response()->download($fullPath, $document->file_name, [
            'Content-Type' => $document->mime_type,
        ]);
    })->name('documents.download');
});