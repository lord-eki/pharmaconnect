<?php

use App\Http\Controllers\CommissionStatementController;
use App\Http\Controllers\InsuranceClaimController;
use App\Http\Controllers\OrderPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/insurance-claims/{claim}/pdf', [
        InsuranceClaimController::class,
        'viewPDF',
    ])->name('insurance-claims.pdf');

    Route::get('/insurance-claims/{claim}/download', [
        InsuranceClaimController::class,
        'downloadPDF',
    ])->name('insurance-claims.download');

    Route::get('/documents/{document}/preview', function (App\Models\Document $document) {
        $fullPath = storage_path('app/private/'.$document->file_path);

        abort_if(! file_exists($fullPath), 404, 'File not found.');

        return response()->file($fullPath, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.$document->file_name.'"',
        ]);
    })->name('documents.preview');

    Route::get('/documents/{document}/download', function (App\Models\Document $document) {
        $fullPath = storage_path('app/private/'.$document->file_path);

        abort_if(! file_exists($fullPath), 404, 'File not found.');

        $document->logAccess(auth()->user(), 'download');

        return response()->download($fullPath, $document->file_name, [
            'Content-Type' => $document->mime_type,
        ]);
    })->name('documents.download');

    Route::get(
        '/commissions/statement',
        [CommissionStatementController::class, 'download']
    )->name('commissions.statement');

    Route::get('/delivery-note/{document}', function (\App\Models\Document $document) {
        $path = storage_path('app/private/'.$document->file_path);

        abort_unless(file_exists($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    })->name('delivery-note.view');

    Route::get(
        '/supplier/orders/{order}/print',
        [OrderPrintController::class, 'stream']
    )->name('supplier.orders.print')->middleware(['auth', 'verified']);

    Route::get(
        '/supplier/orders/{order}/download',
        [OrderPrintController::class, 'download']
    )->name('supplier.orders.download')->middleware(['auth', 'verified']);
});
