
<?php

// routes/api.php

use App\Http\Controllers\Api\DocumentApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| EDMS API Routes
|--------------------------------------------------------------------------
|
| These routes handle document management for external access
| (insurers, suppliers, physicians via API)
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Document Management Routes
    Route::prefix('documents')->name('documents.')->group(function () {
        
        // List and search documents
        Route::get('/', [DocumentApiController::class, 'index'])->name('index');
        
        // Upload new document
        Route::post('/', [DocumentApiController::class, 'store'])->name('store');
        
        // Get specific document
        Route::get('/{document}', [DocumentApiController::class, 'show'])->name('show');
        
        // Download document
        Route::get('/{document}/download', [DocumentApiController::class, 'download'])->name('download');
        
        // Verify document (insurers/admins only)
        Route::post('/{document}/verify', [DocumentApiController::class, 'verify'])->name('verify');
        
        // Reject document (insurers/admins only)
        Route::post('/{document}/reject', [DocumentApiController::class, 'reject'])->name('reject');
        
        // Get documents by entity
        Route::get('/claim/{claimId}', [DocumentApiController::class, 'getClaimDocuments'])->name('claim');
        Route::get('/order/{orderId}', [DocumentApiController::class, 'getOrderDocuments'])->name('order');
        Route::get('/prescription/{prescriptionId}', [DocumentApiController::class, 'getPrescriptionDocuments'])->name('prescription');
        
        // Statistics
        Route::get('/stats/overview', [DocumentApiController::class, 'statistics'])->name('statistics');
    });
});