<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;

/*
|--------------------------------------------------------------------------
| Web Routes - SmartClaim Expense Management System
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Optimized with synchronized names.
|
*/

/**
 * Authentication Gateways
 * Routes dedicated to handling user entry, credentials, and sessions.
 */
Route::get('/', [AuthController::class, 'showLogin'])->name('login');

// Synchronized target naming convention to match login.blade.php form submission
Route::post('/login/process', [AuthController::class, 'processLogin'])->name('login.process');


/**
 * SmartClaim Protected Core Application Routes
 * Handles relational database synchronization, dynamic metrics, and OCR views.
 */
Route::group([], function () {

    // =================================================================
    // 🏠 EMPLOYEE / STAFF AREA ROUTES
    // =================================================================

    // Core Analytics Dashboard Interface View Route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // New Claim Submission Form View Route (SaaS Design layout)
    Route::get('/claims/create', [ClaimController::class, 'create'])->name('claims.create');

    // Complete Historical Transactions Database Logs View Route
    Route::get('/claims/history', [ClaimController::class, 'history'])->name('claims.history');

    // Form Action Target: Handles Image Storage and OCR Engine Extraction 
    Route::post('/claims/store', [ClaimController::class, 'store'])->name('claims.store');

    // Structural view routing for profiles, rules policies, and reimbursements
    Route::get('/profile', [ClaimController::class, 'profileIndex'])->name('profile.index');
    Route::get('/policy', [ClaimController::class, 'policyIndex'])->name('policy.index');
    Route::get('/reimbursement', [ClaimController::class, 'reimbursementIndex'])->name('reimbursement.index');

    // =================================================================
    // 🔍 AUTOMATED AI OCR & LOGISTICS UTILITY DATA ENGINE PIPELINES
    // =================================================================

    // Route for dynamic async real-time OCR extraction when receipt file is selected
    Route::post('/claims/async-scan', [ClaimController::class, 'asyncScan'])->name('claims.asyncScan');

    // Route for runtime tracking check validations on database duplicates logs
    Route::post('/claims/check-duplicate', [ClaimController::class, 'checkDuplicate'])->name('claims.checkDuplicate');

    // =================================================================
    // 💸 ACCOUNT LEDGER AUDITOR WORKSPACE (FINANCE PORTAL AREA)
    // =================================================================

    // Main Executive Finance Board monitoring view route
    Route::get('/finance/dashboard', [ClaimController::class, 'financeIndex'])->name('finance.dashboard');

    // Standalone dedicated workspace desk for live auditing operations
    Route::get('/finance/auditing', [ClaimController::class, 'auditingIndex'])->name('finance.auditing');

    // Action validation route to update claim data states (Shared between Finance and Manager tiers)
    Route::post('/finance/claims/{id}/status', [ClaimController::class, 'updateStatus'])->name('finance.claims.status');

    // Remaining clean compliance view routing paths for Finance Auditing Portal
    Route::get('/finance/reports', [ClaimController::class, 'financeReportsIndex'])->name('finance.reports');
    Route::get('/finance/profile', [ClaimController::class, 'financeProfileIndex'])->name('finance.profile');

    // =================================================================
    // 👑 EXECUTIVE APPROVING WORKSPACE (MANAGER PORTAL AREA)
    // =================================================================

    // High-tier decision interface desk for final executive authorization sign-offs
    Route::get('/manager/dashboard', [ClaimController::class, 'managerIndex'])->name('manager.dashboard');

    // Dedicated high-tier decision interface desk for claim authorization actions
    Route::get('/manager/verification', [ClaimController::class, 'managerVerificationIndex'])->name('manager.verification');

    // 🟢 SYNCHRONIZED ADMINISTRATIVE MANAGEMENT CONFIGURATION PANELS
    // Re-mapped from finance layer to manager authority node mapping schema to enforce separation of duties.
    Route::get('/manager/mileage-rates', [ClaimController::class, 'mileageRatesIndex'])->name('manager.mileage_rates');
    Route::get('/manager/expense-categories', [ClaimController::class, 'expenseCategoriesIndex'])->name('manager.expense_categories');
    Route::get('/manager/user-management', [ClaimController::class, 'userManagementIndex'])->name('manager.user_management');
    Route::get('/manager/audit-logs', [ClaimController::class, 'auditLogsIndex'])->name('manager.audit_logs');

    // 🟢 COMPANY FLEET VEHICLES FULL FUNCTIONAL CRUD MANAGEMENT CONTROL PIPELINES
    // Re-routed from ClaimController to VehicleController to process raw database mutations seamlessly.
    Route::get('/manager/vehicles', [VehicleController::class, 'index'])->name('manager.vehicles');
    Route::post('/manager/vehicles', [VehicleController::class, 'store'])->name('manager.vehicles.store');
    Route::put('/manager/vehicles/{id}', [VehicleController::class, 'update'])->name('manager.vehicles.update');
    Route::delete('/manager/vehicles/{id}', [VehicleController::class, 'destroy'])->name('manager.vehicles.destroy');


// Pastikan guna method POST
    Route::post('/manager/claims/{id}/status', [ClaimController::class, 'updateStatus'])->name('manager.claims.status');    // Reeport PAGES

    Route::get('/manager/reports', [ClaimController::class, 'managerReportsIndex'])->name('manager.reports');

    // MANAGER PROFILE PAGES
    Route::get('/manager/profile', [ClaimController::class, 'managerProfileIndex'])->name('manager.profile');
    // =================================================================
    // 🚪 SESSION TERMINATION LOGOUT
    // =================================================================

    // Terminate active authenticated user session route
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

});