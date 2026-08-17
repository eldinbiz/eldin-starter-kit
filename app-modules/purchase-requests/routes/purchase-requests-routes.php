<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\PurchaseRequests\Http\Controllers\Api\v1\PurchaseRequestController as PurchaseRequestApiController;
use Modules\PurchaseRequests\Http\Controllers\PurchaseRequestController;

// Web Routes
Route::middleware(['web', 'auth'])->prefix('purchase-requests')->name('purchase-requests.')->group(function () {
    Route::get('/', [PurchaseRequestController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseRequestController::class, 'create'])->name('create');
    Route::post('/', [PurchaseRequestController::class, 'store'])->name('store');
    Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
    Route::get('/{purchaseRequest}/preview-workflow', [PurchaseRequestController::class, 'previewWorkflow'])->name('preview-workflow');
    Route::post('/{purchaseRequest}/submit-for-approval', [PurchaseRequestController::class, 'submitForApproval'])->name('submit-for-approval');
});

// API Routes
Route::middleware(['api', 'auth:sanctum', 'api.company'])->prefix('api/v1')->group(function () {
    Route::get('purchases', [PurchaseRequestApiController::class, 'index'])->middleware('ability:purchases:index');
    Route::get('purchases/{purchaseRequest}', [PurchaseRequestApiController::class, 'show'])->middleware('ability:purchases:show');
});
