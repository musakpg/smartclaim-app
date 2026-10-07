<?php

use Illuminate\Support\Facades\Route;

// Authentication & Core Gateways
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileAccessController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\PolicyConfigController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\AuditLogController;

// Staff Domain Controllers
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\ClaimSubmissionController;
use App\Http\Controllers\Staff\VehicleController as StaffVehicleController;
use App\Http\Controllers\Staff\CashAdvanceRequestController;

// Manager Domain Controllers
use App\Http\Controllers\Manager\ManagerDashboardController;
use App\Http\Controllers\Manager\ClaimVerificationController;
use App\Http\Controllers\Manager\VehicleVerificationController;
use App\Http\Controllers\Manager\CashAdvanceApprovalController;
use App\Http\Controllers\Manager\VehicleUsageHistoryController;
use App\Http\Controllers\Manager\ModelEvaluationController;
use App\Http\Controllers\Manager\ExpensePolicyController;
use App\Http\Controllers\Manager\AiFeedbackController;
use App\Http\Controllers\Manager\CategoryController;
use App\Http\Controllers\Manager\MileageRateController;
use App\Http\Controllers\Manager\AuditReasonController;

// Finance Domain Controllers
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\ClaimAuditingController;
use App\Http\Controllers\Finance\DisbursementController;
use App\Http\Controllers\Finance\CashAdvanceReconciliationController;

// Admin & Procurement Domain Controllers
use App\Http\Controllers\Admin\CompanyFleetController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Procurement\PriceIntelligenceController;

use App\Models\User;
use App\Notifications\WebPushNotification;
use NotificationChannels\WebPush\PushSubscription;

/*
|--------------------------------------------------------------------------
| Web Routes - SmartClaim Expense Management System
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| 1. Authentication Gateways
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login/process', [AuthController::class, 'processLogin'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Staff Registration Gateways
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'processRegister'])->name('register.process');

// Account Activation & Password Setup
Route::get('/setup-password/{token}', [AuthController::class, 'showSetupPassword'])->name('password.setup');
Route::post('/setup-password/{token}', [AuthController::class, 'processSetupPassword'])->name('password.setup.process');

// Forgot & Reset Password
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'processResetPassword'])->name('password.update');


/*
|--------------------------------------------------------------------------
| 2. Executive Approving Workspace (Manager Portal)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:Manager'])->prefix('manager')->name('manager.')->group(function () {
    // Dashboard & Claims Verification Desks
    Route::get('/dashboard', [ManagerDashboardController::class, 'managerIndex'])->name('dashboard');
    Route::get('/verification', [ClaimVerificationController::class, 'index'])->name('verification');
    Route::post('/claims/{id}/status', [ClaimVerificationController::class, 'updateStatus'])->name('claims.status');

    // Fleet Management & Vehicle Auditing
    Route::get('/vehicles', [CompanyFleetController::class, 'managerIndex'])->name('vehicles');
    Route::post('/vehicles', [CompanyFleetController::class, 'storeCompanyFleet'])->name('vehicles.store');
    Route::put('/vehicles/{id}', [CompanyFleetController::class, 'updateCompanyFleet'])->name('vehicles.update');
    Route::delete('/vehicles/{id}', [CompanyFleetController::class, 'destroyCompanyFleet'])->name('vehicles.destroy');
    Route::post('/vehicles/{id}/verify', [VehicleVerificationController::class, 'verifyStaffVehicle'])->name('vehicles.verify');
    Route::post('/vehicles/{id}/approve', [VehicleVerificationController::class, 'approve'])->name('vehicles.approve');
    Route::post('/vehicles/{id}/reject', [VehicleVerificationController::class, 'reject'])->name('vehicles.reject');
    Route::get('/vehicle-usage-history', [VehicleUsageHistoryController::class, 'index'])->name('vehicle_history');
    Route::get('/vehicle-usage-history/export', [VehicleUsageHistoryController::class, 'exportCsv'])->name('vehicle_history.export');

    // Administration Configuration
    Route::get('/user-management', [UserManagementController::class, 'userManagementIndex'])->name('user_management');
    Route::post('/user-management/{id}/toggle-status', [UserManagementController::class, 'toggleUserStatus'])->name('user_management.toggle');
    Route::get('/expense-policies', [ExpensePolicyController::class, 'index'])->name('expense_policies');
    Route::put('/expense-policies/{id}', [ExpensePolicyController::class, 'update'])->name('expense_policies.update');
    Route::delete('/expense-policies/{id}', [ExpensePolicyController::class, 'destroy'])->name('expense_policies.destroy');
    Route::get('/expense-categories', [CategoryController::class, 'index'])->name('expense_categories');
    Route::post('/expense-categories', [CategoryController::class, 'store'])->name('expense_categories.store');
    Route::put('/expense-categories/{id}', [CategoryController::class, 'update'])->name('expense_categories.update');
    Route::delete('/expense-categories/{id}', [CategoryController::class, 'destroy'])->name('expense_categories.destroy');

    Route::get('/mileage-rates', [MileageRateController::class, 'index'])->name('mileage_rates');
    Route::post('/mileage-rates', [MileageRateController::class, 'store'])->name('mileage_rates.store');
    Route::put('/mileage-rates/{id}', [MileageRateController::class, 'update'])->name('mileage_rates.update');
    Route::delete('/mileage-rates/{id}', [MileageRateController::class, 'destroy'])->name('mileage_rates.destroy');
    Route::get('/audit-logs', [AuditLogController::class, 'auditLogsIndex'])->name('audit_logs');
    Route::get('/audit-reasons', [AuditReasonController::class, 'index'])->name('audit_reasons');
    Route::post('/audit-reasons', [AuditReasonController::class, 'store'])->name('audit_reasons.store');
    Route::put('/audit-reasons/{id}', [AuditReasonController::class, 'update'])->name('audit_reasons.update');
    Route::post('/audit-reasons/{id}/toggle', [AuditReasonController::class, 'toggle'])->name('audit_reasons.toggle');
    Route::delete('/audit-reasons/{id}', [AuditReasonController::class, 'destroy'])->name('audit_reasons.destroy');

    // AI & NLP Model Benchmark Evaluation
    Route::get('/model-evaluation', [ModelEvaluationController::class, 'index'])->name('model-evaluation');
    Route::post('/model-evaluation/run', [ModelEvaluationController::class, 'runBenchmark'])->name('model-evaluation.run');
    Route::post('/model-evaluation/upload', [ModelEvaluationController::class, 'uploadAndEvaluate'])->name('model-evaluation.upload');
    Route::get('/api/benchmark/metrics', [ModelEvaluationController::class, 'getMetricsApi'])->name('api.benchmark.metrics');
    Route::get('/ai-feedback', [AiFeedbackController::class, 'index'])->name('ai_feedback');
    Route::post('/ai-feedback/{id}/toggle', [AiFeedbackController::class, 'toggle'])->name('ai_feedback.toggle');

    // Intelligence, Analytics & Export
    Route::get('/price-intelligence', [PriceIntelligenceController::class, 'priceIntelligenceIndex'])->name('price_intelligence');
    Route::get('/reports', [ManagerDashboardController::class, 'managerReportsIndex'])->name('reports');
    Route::get('/reports/sla-analytics', [ClaimVerificationController::class, 'slaAnalytics'])->name('reports.sla_analytics');
    Route::get('/export/claims-csv', [ExportController::class, 'exportClaimsCsv'])->name('export.claims_csv');
    Route::get('/profile', [ManagerDashboardController::class, 'managerProfileIndex'])->name('profile');

    // Manager Cash Advances Desk
    Route::get('/cash-advances', [CashAdvanceApprovalController::class, 'managerIndex'])->name('advances.index');
    Route::post('/cash-advances/{id}/status', [CashAdvanceApprovalController::class, 'updateStatus'])->name('advances.status');
});


/*
|--------------------------------------------------------------------------
| 3. Account Ledger Auditor Workspace (Finance Portal)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:Finance'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/staff-directory', [FinanceDashboardController::class, 'financeStaffDirectoryIndex'])->name('staff_directory');
    // Dashboard & Auditing
    Route::get('/dashboard', [FinanceDashboardController::class, 'financeIndex'])->name('dashboard');
    Route::get('/auditing', [ClaimAuditingController::class, 'index'])->name('auditing');
    Route::post('/claims/{id}/status', [ClaimAuditingController::class, 'updateStatus'])->name('claims.status');

    // Cash Advance Reconciliation
    Route::get('/cash-advances', [CashAdvanceReconciliationController::class, 'financeIndex'])->name('cash-advances.index');

    // Unified Payment Disbursement Desk (Single & Batch with AI Slip OCR)
    Route::get('/disbursement', [DisbursementController::class, 'index'])->name('disbursement');
    Route::post('/disbursement/{id}/settle', [DisbursementController::class, 'processDisbursement'])->name('disbursement.settle');
    Route::post('/disbursement/batch-settle', [DisbursementController::class, 'processBatchDisbursement'])->name('disbursement.batch_settle');
    Route::post('/disbursement/scan-slip', [DisbursementController::class, 'asyncScanBankSlip'])->name('disbursement.scan_slip');

    // Reports & Profile
    Route::get('/reports', [FinanceDashboardController::class, 'financeReportsIndex'])->name('reports');
    Route::get('/profile', [FinanceDashboardController::class, 'financeProfileIndex'])->name('profile');
});


/*
|--------------------------------------------------------------------------
| 4. Employee / Staff Area Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Core Claims Management
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
    Route::get('/claims/create', [ClaimSubmissionController::class, 'create'])->name('claims.create');
    Route::get('/claims/history', [ClaimSubmissionController::class, 'history'])->name('claims.history');
    Route::get('/claims/{id}/edit', [ClaimSubmissionController::class, 'edit'])->name('claims.edit');
    Route::put('/claims/{id}/resubmit', [ClaimSubmissionController::class, 'resubmit'])->name('claims.resubmit');
    Route::post('/claims/{id}/withdraw', [ClaimSubmissionController::class, 'withdraw'])->name('claims.withdraw');
    Route::post('/claims/store', [ClaimSubmissionController::class, 'store'])->name('claims.store');
    Route::post('/claims/async-scan', [ClaimSubmissionController::class, 'asyncScan'])->name('claims.asyncScan');
    Route::post('/claims/check-duplicate', [ClaimSubmissionController::class, 'checkDuplicate'])->name('claims.checkDuplicate');
    Route::get('/claims/{id}/voucher-pdf', [ExportController::class, 'downloadVoucherPdf'])->name('claims.voucher_pdf');

    // Information & Payout Status for Staff
    Route::get('/policy', [PolicyConfigController::class, 'policyIndex'])->name('policy.index');
    Route::get('/reimbursement', [ClaimSubmissionController::class, 'reimbursementIndex'])->name('reimbursement.index');
    Route::get('/profile', [UserProfileController::class, 'profileIndex'])->name('profile.index');
    Route::put('/profile/update', [UserProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [UserProfileController::class, 'updatePassword'])->name('profile.password');

    // Staff Personal Vehicles Management
    Route::get('/vehicles', [StaffVehicleController::class, 'staffIndex'])->name('vehicles.index');
    Route::get('/vehicles/create', [StaffVehicleController::class, 'staffCreate'])->name('vehicles.create');
    Route::post('/vehicles', [StaffVehicleController::class, 'staffStore'])->name('vehicles.store');
    Route::get('/vehicles/{id}/edit', [StaffVehicleController::class, 'staffEdit'])->name('vehicles.edit');
    Route::put('/vehicles/{id}', [StaffVehicleController::class, 'staffUpdate'])->name('vehicles.update');
    Route::post('/vehicles/{id}/renew', [StaffVehicleController::class, 'renewRoadtax'])->name('vehicles.renew');
    Route::delete('/vehicles/{id}', [StaffVehicleController::class, 'staffDestroy'])->name('vehicles.destroy');

    // Staff Cash Advances
    Route::get('/cash-advances', [CashAdvanceRequestController::class, 'index'])->name('advances.index');
    Route::post('/cash-advances', [CashAdvanceRequestController::class, 'store'])->name('advances.store');

    // Shared Notifications & Push Subscriptions
    Route::get('/api/notifications/latest', [NotificationController::class, 'fetchLatest'])->name('notifications.latest');
    Route::post('/api/notifications/mark-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark_read');
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
});


/*
|--------------------------------------------------------------------------
| 5. Development & Testing Diagnostics
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:Manager'])->get('/test-push', function () {
    $subCount = PushSubscription::count();
    if ($subCount === 0) {
        return 'Tiada peranti berdaftar dalam database table push_subscriptions. Sila tekan Sync Device di telefon dahulu.';
    }

    $sub = PushSubscription::latest()->first();
    $user = User::where('user_id', $sub->subscribable_id)->first();

    if (!$user) {
        return "User dengan user_id: {$sub->subscribable_id} tidak dijumpai.";
    }

    try {
        $user->notify(new WebPushNotification(
            '🔔 Status Tuntutan Dikemas Kini!',
            'Baucar anda #CLM-5 telah disahkan oleh Finance.',
            url('/dashboard')
        ));

        return "BERJAYA: Signal notifikasi telah dihantar kepada User ID: {$user->user_id}! Sila semak telefon anda sekarang.";
    } catch (\Throwable $e) {
        return 'Ralat Penghantaran WebPush: ' . $e->getMessage();
    }

});

// Download Official PDF Claim Voucher (Unified with ExportController)
Route::get('/claims/{id}/download-pdf', [ExportController::class, 'downloadVoucherPdf'])
    ->name('claims.download_pdf')
    ->middleware('auth');

// Secured Private File Storage Serving
Route::get('/files/{folder}/{filename}', [FileAccessController::class, 'serveFile'])
    ->name('files.serve')
    ->middleware('auth')
    ->where('filename', '.*');
