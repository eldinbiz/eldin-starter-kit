<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CompanyController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\RoleController;
use App\Http\Controllers\Api\v1\ApprovalController;
use App\Http\Controllers\Api\v1\PurchaseRequestController;
use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\AuditLogController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api.v1'])->group(function () {
    // Auth / Profile
    Route::get('me', [AuthController::class, 'me']);
    
    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);

    // Admin / Platform Routes
    Route::middleware('can:manage-platform')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
        
        Route::get('roles', [RoleController::class, 'index']);
        Route::get('roles/{role}', [RoleController::class, 'show']);
        
        Route::get('companies', [CompanyController::class, 'index']);
        Route::get('companies/hierarchy', [CompanyController::class, 'hierarchy']);
        Route::get('companies/{company}', [CompanyController::class, 'show']);
        
        Route::get('audit-logs', [AuditLogController::class, 'index']);
    });

    // Company Scoped Routes (Require X-Company-ID header and Membership)
    Route::middleware(['api.company'])->group(function () {
        Route::get('approvals', [ApprovalController::class, 'index']);
        Route::get('approvals/{approval}', [ApprovalController::class, 'show']);
        
        Route::get('purchases', [PurchaseRequestController::class, 'index']);
        Route::get('purchases/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
    });
});
