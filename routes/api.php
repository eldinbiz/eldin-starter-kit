<?php

use App\Http\Controllers\Api\v1\ApprovalController;
use App\Http\Controllers\Api\v1\AuditLogController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CompanyController;
use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\PurchaseRequestController;
use App\Http\Controllers\Api\v1\RoleController;
use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api.v1'])->group(function () {
    // Auth / Profile
    Route::get('me', [AuthController::class, 'me'])->middleware('ability:auth:me');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->middleware('ability:notifications:index');

    // Admin / Platform Routes
    Route::middleware('can:manage-platform')->group(function () {
        Route::get('users', [UserController::class, 'index'])->middleware('ability:users:index');
        Route::get('users/{user}', [UserController::class, 'show'])->middleware('ability:users:show');

        Route::get('roles', [RoleController::class, 'index'])->middleware('ability:roles:index');
        Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('ability:roles:show');

        Route::get('companies', [CompanyController::class, 'index'])->middleware('ability:companies:index');
        Route::get('companies/hierarchy', [CompanyController::class, 'hierarchy'])->middleware('ability:companies:hierarchy');
        Route::get('companies/{company}', [CompanyController::class, 'show'])->middleware('ability:companies:show');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('ability:audit-logs:index');
    });

    // Company Scoped Routes (Require X-Company-ID header and Membership)
    Route::middleware(['api.company', 'feature:api_access'])->group(function () {
        Route::get('approvals', [ApprovalController::class, 'index'])->middleware('ability:approvals:index');
        Route::get('approvals/{approval}', [ApprovalController::class, 'show'])->middleware('ability:approvals:show');

        Route::get('purchases', [PurchaseRequestController::class, 'index'])->middleware('ability:purchases:index');
        Route::get('purchases/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->middleware('ability:purchases:show');
    });
});
