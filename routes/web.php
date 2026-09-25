<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Manager\VehicleUsageHistoryController;
use App\Http\Controllers\Manager\ModelEvaluationController;
use App\Http\Controllers\Manager\ExpensePolicyController;
use App\Http\Controllers\Manager\AiFeedbackController;

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
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');


/*
|--------------------------------------------------------------------------
| 2. 👑 Executive Approving Workspace (Manager Portal)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:Manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/user-management', [ClaimController::class, 'userManagementIndex'])->name('user_management');
    // Dashboard & Claims Verification Desks
    Route::get('/dashboard', [ClaimController::class, 'managerIndex'])->name('dashboard');
    Route::get('/verification', [ClaimController::class, 'managerVerificationIndex'])->name('verification');
    Route::post('/claims/{id}/status', [ClaimController::class, 'updateStatus'])->name('claims.status');

    // Fleet Management & Vehicle Auditing
    Route::get('/vehicles', [VehicleController::class, 'managerIndex'])->name('vehicles');
    Route::post('/vehicles', [VehicleController::class, 'storeCompanyFleet'])->name('vehicles.store');
    Route::put('/vehicles/{id}', [VehicleController::class, 'updateCompanyFleet'])->name('vehicles.update');
    Route::delete('/vehicles/{id}', [VehicleController::class, 'destroyCompanyFleet'])->name('vehicles.destroy');
    Route::post('/vehicles/{id}/verify', [VehicleController::class, 'verifyStaffVehicle'])->name('vehicles.verify');
    Route::post('/vehicles/{id}/approve', [VehicleController::class, 'approve'])->name('vehicles.approve');
    Route::post('/vehicles/{id}/reject', [VehicleController::class, 'reject'])->name('vehicles.reject');
    Route::get('/vehicle-usage-history', [VehicleUsageHistoryController::class, 'index'])->name('vehicle_history');
    Route::get('/vehicle-usage-history/export', [VehicleUsageHistoryController::class, 'exportCsv'])->name('vehicle_history.export');

    // Administration Configuration
    Route::get('/user-management', [ClaimController::class, 'userManagementIndex'])->name('user_management');
    Route::post('/user-management/{id}/toggle-status', [ClaimController::class, 'toggleUserStatus'])->name('user_management.toggle');
    Route::get('/expense-policies', [ExpensePolicyController::class, 'index'])->name('expense_policies');
    Route::put('/expense-policies/{id}', [ExpensePolicyController::class, 'update'])->name('expense_policies.update');
    Route::delete('/expense-policies/{id}', [ExpensePolicyController::class, 'destroy'])->name('expense_policies.destroy');
    Route::get('/expense-categories', [App\Http\Controllers\Manager\CategoryController::class, 'index'])->name('expense_categories');
    Route::post('/expense-categories', [App\Http\Controllers\Manager\CategoryController::class, 'store'])->name('expense_categories.store');
    Route::put('/expense-categories/{id}', [App\Http\Controllers\Manager\CategoryController::class, 'update'])->name('expense_categories.update');
    Route::delete('/expense-categories/{id}', [App\Http\Controllers\Manager\CategoryController::class, 'destroy'])->name('expense_categories.destroy');

    Route::get('/mileage-rates', [App\Http\Controllers\Manager\MileageRateController::class, 'index'])->name('mileage_rates');
    Route::post('/mileage-rates', [App\Http\Controllers\Manager\MileageRateController::class, 'store'])->name('mileage_rates.store');
    Route::put('/mileage-rates/{id}', [App\Http\Controllers\Manager\MileageRateController::class, 'update'])->name('mileage_rates.update');
    Route::delete('/mileage-rates/{id}', [App\Http\Controllers\Manager\MileageRateController::class, 'destroy'])->name('mileage_rates.destroy');
    Route::get('/audit-logs', [ClaimController::class, 'auditLogsIndex'])->name('audit_logs');
    Route::get('/audit-reasons', [App\Http\Controllers\Manager\AuditReasonController::class, 'index'])->name('audit_reasons');
    Route::post('/audit-reasons', [App\Http\Controllers\Manager\AuditReasonController::class, 'store'])->name('audit_reasons.store');
    Route::put('/audit-reasons/{id}', [App\Http\Controllers\Manager\AuditReasonController::class, 'update'])->name('audit_reasons.update');
    Route::post('/audit-reasons/{id}/toggle', [App\Http\Controllers\Manager\AuditReasonController::class, 'toggle'])->name('audit_reasons.toggle');
    Route::delete('/audit-reasons/{id}', [App\Http\Controllers\Manager\AuditReasonController::class, 'destroy'])->name('audit_reasons.destroy');

    // AI & NLP Model Benchmark Evaluation
    Route::get('/model-evaluation', [ModelEvaluationController::class, 'index'])->name('model-evaluation');
    Route::post('/model-evaluation/run', [ModelEvaluationController::class, 'runBenchmark'])->name('model-evaluation.run');
    Route::post('/model-evaluation/upload', [ModelEvaluationController::class, 'uploadAndEvaluate'])->name('model-evaluation.upload');
    Route::get('/api/benchmark/metrics', [ModelEvaluationController::class, 'getMetricsApi'])->name('api.benchmark.metrics');
    Route::get('/ai-feedback', [AiFeedbackController::class, 'index'])->name('ai_feedback');
    Route::post('/ai-feedback/{id}/toggle', [AiFeedbackController::class, 'toggle'])->name('ai_feedback.toggle');

    // Intelligence, Analytics & Export
    Route::get('/price-intelligence', [ClaimController::class, 'priceIntelligenceIndex'])->name('price_intelligence');
    Route::get('/reports', [ClaimController::class, 'managerReportsIndex'])->name('reports');
    Route::get('/reports/sla-analytics', [ClaimController::class, 'slaAnalytics'])->name('reports.sla_analytics');
    Route::get('/export/claims-csv', [ExportController::class, 'exportClaimsCsv'])->name('export.claims_csv');
    Route::get('/profile', [ClaimController::class, 'managerProfileIndex'])->name('profile');

    // Manager Cash Advances Desk
    Route::get('/cash-advances', [CashAdvanceController::class, 'managerIndex'])->name('advances.index');
    Route::post('/cash-advances/{id}/status', [CashAdvanceController::class, 'updateStatus'])->name('advances.status');
});


/*
|--------------------------------------------------------------------------
| 3. 💸 Account Ledger Auditor Workspace (Finance Portal)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:Finance'])->prefix('finance')->name('finance.')->group(function () {
    // Route::post('/finance/disbursement/bulk-settle', [App\Http\Controllers\ReimbursementController::class, 'bulkSettle'])
    //    ->name('finance.disbursement.bulk_settle')
    //    ->middleware('auth');
    Route::get('/staff-directory', [ClaimController::class, 'financeStaffDirectoryIndex'])->name('staff_directory');
    // Dashboard & Auditing
    Route::get('/dashboard', [ClaimController::class, 'financeIndex'])->name('dashboard');
    Route::get('/auditing', [ClaimController::class, 'auditingIndex'])->name('auditing');
    Route::post('/claims/{id}/status', [ClaimController::class, 'updateStatus'])->name('claims.status');

    // Cash Advance Reconciliation
    Route::get('/cash-advances', [\App\Http\Controllers\CashAdvanceController::class, 'financeIndex'])->name('cash-advances.index');

    // Unified Payment Disbursement Desk (Single & Batch with AI Slip OCR)
    Route::get('/disbursement', [ClaimController::class, 'financeDisbursementIndex'])->name('disbursement');
    Route::post('/disbursement/{id}/settle', [ClaimController::class, 'processDisbursement'])->name('disbursement.settle');
    Route::post('/disbursement/batch-settle', [ClaimController::class, 'processBatchDisbursement'])->name('disbursement.batch_settle');
    Route::post('/disbursement/scan-slip', [ClaimController::class, 'asyncScanBankSlip'])->name('disbursement.scan_slip');

    // Reports & Profile
    Route::get('/reports', [ClaimController::class, 'financeReportsIndex'])->name('reports');
    Route::get('/profile', [ClaimController::class, 'financeProfileIndex'])->name('profile');
});


/*
|--------------------------------------------------------------------------
| 4. 🏠 Employee / Staff Area Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Core Claims Management
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/claims/create', [ClaimController::class, 'create'])->name('claims.create');
    Route::get('/claims/history', [ClaimController::class, 'history'])->name('claims.history');
    Route::get('/claims/{id}/edit', [ClaimController::class, 'edit'])->name('claims.edit');
    Route::put('/claims/{id}/resubmit', [ClaimController::class, 'resubmit'])->name('claims.resubmit');
    Route::post('/claims/{id}/withdraw', [ClaimController::class, 'withdraw'])->name('claims.withdraw');
    Route::post('/claims/store', [ClaimController::class, 'store'])->name('claims.store');
    Route::post('/claims/async-scan', [ClaimController::class, 'asyncScan'])->name('claims.asyncScan');
    Route::post('/claims/check-duplicate', [ClaimController::class, 'checkDuplicate'])->name('claims.checkDuplicate');
    Route::get('/claims/{id}/voucher-pdf', [ExportController::class, 'downloadVoucherPdf'])->name('claims.voucher_pdf');

    // Information & Payout Status for Staff
    Route::get('/policy', [ClaimController::class, 'policyIndex'])->name('policy.index');
    Route::get('/reimbursement', [ClaimController::class, 'reimbursementIndex'])->name('reimbursement.index');
    Route::get('/profile', [ClaimController::class, 'profileIndex'])->name('profile.index');
    Route::put('/profile/update', [ClaimController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ClaimController::class, 'updatePassword'])->name('profile.password');

    // Staff Personal Vehicles Management
    Route::get('/vehicles', [VehicleController::class, 'staffIndex'])->name('vehicles.index');
    Route::get('/vehicles/create', [VehicleController::class, 'staffCreate'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleController::class, 'staffStore'])->name('vehicles.store');
    Route::get('/vehicles/{id}/edit', [VehicleController::class, 'staffEdit'])->name('vehicles.edit');
    Route::put('/vehicles/{id}', [VehicleController::class, 'staffUpdate'])->name('vehicles.update');
    Route::post('/vehicles/{id}/renew', [VehicleController::class, 'renewRoadtax'])->name('vehicles.renew');
    Route::delete('/vehicles/{id}', [VehicleController::class, 'staffDestroy'])->name('vehicles.destroy');

    // Staff Cash Advances
    Route::get('/cash-advances', [CashAdvanceController::class, 'index'])->name('advances.index');
    Route::post('/cash-advances', [CashAdvanceController::class, 'store'])->name('advances.store');

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
Route::get('/test-push', function () {
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

// Download Official PDF Claim Voucher
Route::get('/claims/{id}/download-pdf', [App\Http\Controllers\ClaimController::class, 'downloadPdfVoucher'])
    ->name('claims.download_pdf')
    ->middleware('auth');
// Secured Private File Storage Serving
Route::get('/files/{folder}/{filename}', [App\Http\Controllers\ClaimController::class, 'serveFile'])
    ->name('files.serve')
    ->middleware('auth');
