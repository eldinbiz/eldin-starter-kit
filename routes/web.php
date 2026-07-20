<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\OrgChartController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected Application Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Role Switch Route
    Route::post('/switch-role', [RoleController::class, 'switch'])->name('switch-role');

    // Company Switch Route
    Route::post('/switch-company', [CompanyController::class, 'switch'])->name('switch-company');
});

// Administration Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
    Route::post('roles/{role}/restore', [App\Http\Controllers\Admin\RoleController::class, 'restore'])->name('roles.restore');
    Route::delete('roles/{role}/soft-delete', [App\Http\Controllers\Admin\RoleController::class, 'softDelete'])->name('roles.soft-delete');

    // Company Hierarchy Management (Tree View)
    Route::get('/companies', [App\Http\Controllers\Admin\CompanyController::class, 'hierarchy'])->name('companies.index');
    Route::get('/companies/tree', [App\Http\Controllers\Admin\CompanyController::class, 'tree'])->name('companies.tree');
    Route::patch('/companies/reparent', [App\Http\Controllers\Admin\CompanyController::class, 'reparent'])->name('companies.reparent');

    // Company CRUD
    Route::get('/companies/create', [App\Http\Controllers\Admin\CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [App\Http\Controllers\Admin\CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}/edit', [App\Http\Controllers\Admin\CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [App\Http\Controllers\Admin\CompanyController::class, 'update'])->name('companies.update');
    Route::patch('/companies/{company}/toggle', [App\Http\Controllers\Admin\CompanyController::class, 'toggleActive'])->name('companies.toggle');

    // User Assignment within a Company
    Route::post('/companies/{company}/assign-user', [App\Http\Controllers\Admin\CompanyController::class, 'assignUser'])->name('companies.assign-user');
    Route::delete('/companies/{company}/remove-user/{user}', [App\Http\Controllers\Admin\CompanyController::class, 'removeUser'])->name('companies.remove-user');

    // Company Assignment from User Management
    Route::post('/users/{user}/assign-company', [UserController::class, 'assignCompany'])->name('users.assign-company');
    Route::delete('/users/{user}/remove-company/{company}', [UserController::class, 'removeCompany'])->name('users.remove-company');

    // Organization Chart Routes
    Route::get('/org-chart', [OrgChartController::class, 'index'])->name('org-chart.index');
    Route::get('/org-chart/tree', [OrgChartController::class, 'tree'])->name('org-chart.tree');
    Route::patch('/org-chart/reassign', [OrgChartController::class, 'updateManager'])->name('org-chart.reassign');
    Route::get('/org-chart/trace/{user}', [OrgChartController::class, 'trace'])->name('org-chart.trace');
});
