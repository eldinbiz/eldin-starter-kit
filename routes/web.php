<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\ApprovalManagementController;
use App\Http\Controllers\Admin\ApprovalWorkflowController;
use App\Http\Controllers\Admin\AuthProviderSettingController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\OrgChartController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/lookup', [AuthController::class, 'lookup'])->name('login.lookup');

    Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])->name('auth.callback');
    Route::post('/auth/{provider}/callback', [AuthController::class, 'callback']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected Application Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Impersonation Routes
    Route::impersonate();

    // Role Switch Route
    Route::post('/switch-role', [RoleController::class, 'switch'])->name('switch-role');

    // Company Switch Route
    Route::post('/switch-company', [CompanyController::class, 'switch'])->name('switch-company');

    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');

    // Organization Chart Routes (View for all roles with view-org-chart, Manage for SysAdmin & Company Admin)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('can:view-org-chart')->group(function () {
            Route::get('/org-chart', [OrgChartController::class, 'index'])->name('org-chart.index');
            Route::get('/org-chart/tree', [OrgChartController::class, 'tree'])->name('org-chart.tree');
            Route::get('/org-chart/trace/{user}', [OrgChartController::class, 'trace'])->name('org-chart.trace');
        });
        Route::patch('/org-chart/reassign', [OrgChartController::class, 'updateManager'])
            ->name('org-chart.reassign')
            ->middleware('can:manage-org-chart');
    });
});

// Administration Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // System Auth Provider Settings
    Route::middleware('can:manage-auth-providers')->group(function () {
        Route::get('/settings/auth-providers', [AuthProviderSettingController::class, 'index'])->name('settings.auth-providers.index');
        Route::put('/settings/auth-providers/{provider}', [AuthProviderSettingController::class, 'update'])->name('settings.auth-providers.update');
        Route::post('/settings/auth-providers/{provider}/test', [AuthProviderSettingController::class, 'test'])->name('settings.auth-providers.test');
    });

    // System Settings (Platform-Wide & Custom Variables & Email Templates)
    Route::middleware('can:manage-system-settings')->group(function () {
        Route::get('/settings', fn () => redirect()->route('admin.settings.general'))->name('settings.index');
        Route::get('/settings/general', [SystemSettingController::class, 'general'])->name('settings.general');
        Route::put('/settings/general', [SystemSettingController::class, 'updateGeneral'])->name('settings.general.update');

        Route::get('/settings/mail', [SystemSettingController::class, 'mail'])->name('settings.mail');
        Route::put('/settings/mail', [SystemSettingController::class, 'updateMail'])->name('settings.mail.update');
        Route::post('/settings/mail/test', [SystemSettingController::class, 'testMail'])->name('settings.mail.test');

        Route::get('/settings/custom-variables', [SystemSettingController::class, 'customVariables'])->name('settings.custom-variables');
        Route::post('/settings/custom-variables/check', [SystemSettingController::class, 'checkCustomVariableKey'])->name('settings.custom-variables.check');
        Route::post('/settings/custom-variables', [SystemSettingController::class, 'storeCustomVariable'])->name('settings.custom-variables.store');
        Route::put('/settings/custom-variables/{id}', [SystemSettingController::class, 'updateCustomVariable'])->name('settings.custom-variables.update');
        Route::delete('/settings/custom-variables/{id}', [SystemSettingController::class, 'destroyCustomVariable'])->name('settings.custom-variables.destroy');

        Route::get('/settings/email-templates', [EmailTemplateController::class, 'index'])->name('settings.email-templates.index');
        Route::get('/settings/email-templates/create', [EmailTemplateController::class, 'create'])->name('settings.email-templates.create');
        Route::post('/settings/email-templates', [EmailTemplateController::class, 'store'])->name('settings.email-templates.store');
        Route::post('/settings/email-templates/preview-draft', [EmailTemplateController::class, 'previewDraft'])->name('settings.email-templates.preview-draft');
        Route::get('/settings/email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('settings.email-templates.edit');
        Route::put('/settings/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('settings.email-templates.update');
        Route::delete('/settings/email-templates/{emailTemplate}', [EmailTemplateController::class, 'destroy'])->name('settings.email-templates.destroy');
        Route::get('/settings/email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('settings.email-templates.preview');
        Route::post('/settings/email-templates/{emailTemplate}/test', [EmailTemplateController::class, 'test'])->name('settings.email-templates.test');
    });

    // Company Settings (Tenant Preferences)
    Route::middleware('can:manage-company-settings')->group(function () {
        Route::get('/settings/company', [SystemSettingController::class, 'company'])->name('settings.company');
        Route::put('/settings/company', [SystemSettingController::class, 'updateCompany'])->name('settings.company.update');
    });

    // Feature Toggles (Platform Admin only)
    Route::middleware('can:manage-platform')->group(function () {
        Route::get('/settings/features', [SystemSettingController::class, 'features'])->name('settings.features');
        Route::put('/settings/features', [SystemSettingController::class, 'updateFeatures'])->name('settings.features.update');
    });

    // Users & Roles Management
    Route::middleware('can:manage-users')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('/users/{user}/assign-company', [UserController::class, 'assignCompany'])->name('users.assign-company');
        Route::delete('/users/{user}/remove-company/{company}', [UserController::class, 'removeCompany'])->name('users.remove-company');
    });

    Route::middleware('can:manage-roles')->group(function () {
        Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
        Route::post('roles/{role}/restore', [App\Http\Controllers\Admin\RoleController::class, 'restore'])->name('roles.restore')->withTrashed();
        Route::delete('roles/{role}/soft-delete', [App\Http\Controllers\Admin\RoleController::class, 'softDelete'])->name('roles.soft-delete');
    });

    // Company Hierarchy Management (View: SysAdmin & Company Admin)
    Route::middleware('can:view-companies')->group(function () {
        Route::get('/companies', [App\Http\Controllers\Admin\CompanyController::class, 'hierarchy'])->name('companies.index');
        Route::get('/companies/tree', [App\Http\Controllers\Admin\CompanyController::class, 'tree'])->name('companies.tree');
    });

    // Company Management Mutations (Manage: SysAdmin Only)
    Route::middleware('can:manage-companies')->group(function () {
        Route::patch('/companies/reparent', [App\Http\Controllers\Admin\CompanyController::class, 'reparent'])->name('companies.reparent');
        Route::get('/companies/create', [App\Http\Controllers\Admin\CompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [App\Http\Controllers\Admin\CompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{company}/edit', [App\Http\Controllers\Admin\CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [App\Http\Controllers\Admin\CompanyController::class, 'update'])->name('companies.update');
        Route::patch('/companies/{company}/toggle', [App\Http\Controllers\Admin\CompanyController::class, 'toggleActive'])->name('companies.toggle');
        Route::post('/companies/{company}/assign-user', [App\Http\Controllers\Admin\CompanyController::class, 'assignUser'])->name('companies.assign-user');
        Route::delete('/companies/{company}/remove-user/{user}', [App\Http\Controllers\Admin\CompanyController::class, 'removeUser'])->name('companies.remove-user');
    });

    // Approval Management
    Route::middleware(['can:manage-approvals', 'feature:approvals'])->group(function () {
        Route::get('/approval-management', [ApprovalManagementController::class, 'index'])
            ->name('approval-management.index');
        Route::post('/approval-management/{step}/delegate', [ApprovalManagementController::class, 'delegate'])
            ->name('approval-management.delegate');
        Route::post('/approval-management/{step}/skip', [ApprovalManagementController::class, 'skip'])
            ->name('approval-management.skip');
    });

    // Approval Workflow Templates
    Route::middleware(['can:manage-approval-workflows', 'feature:approvals'])->group(function () {
        Route::resource('approval-workflows', ApprovalWorkflowController::class)->parameters([
            'approval-workflows' => 'workflow',
        ]);
        Route::post('approval-workflows/{workflow}/restore', [ApprovalWorkflowController::class, 'restore'])->name('approval-workflows.restore')->withTrashed();
        Route::delete('approval-workflows/{workflow}/soft-delete', [ApprovalWorkflowController::class, 'softDelete'])->name('approval-workflows.soft-delete')->withTrashed();
        Route::delete('approval-workflows/{workflow}/trash', [ApprovalWorkflowController::class, 'trash'])->name('approval-workflows.trash');
    });

    // API Token Governance & Interactive Documentation
    Route::middleware(['can:manage-api-tokens', 'feature:api_access'])->group(function () {
        Route::resource('api-tokens', ApiTokenController::class)->only(['index', 'store', 'destroy']);
        Route::get('api-docs', fn () => view('admin.api-docs.index'))->name('api-docs.index');
    });
});

// Approval Routes (authenticated users)
Route::middleware(['auth', 'feature:approvals'])->prefix('approvals')->name('approvals.')->group(function () {
    Route::get('/inbox', [ApprovalController::class, 'inbox'])->name('inbox')->middleware('can:view-approval-inbox');
    Route::get('/history', [ApprovalController::class, 'history'])->name('history')->middleware('can:view-approval-history');
    Route::get('/requests', [ApprovalController::class, 'requests'])->name('requests')->middleware('can:view-approval-requests');
    Route::get('/{request}', [ApprovalController::class, 'show'])->name('show');
    Route::post('/{step}/approve', [ApprovalController::class, 'approve'])->name('approve');
    Route::post('/{step}/reject', [ApprovalController::class, 'reject'])->name('reject');
    Route::post('/{request}/cancel', [ApprovalController::class, 'cancel'])->name('cancel');
    Route::post('/{step}/delegate', [ApprovalController::class, 'delegate'])->name('delegate');
    Route::post('/{step}/resolve-previous-manager', [ApprovalController::class, 'resolvePreviousManager'])->name('resolve-previous-manager');
});
