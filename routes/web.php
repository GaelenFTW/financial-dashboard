<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagementReportController;
use App\Http\Controllers\PurchaseLetterController;
use App\Http\Controllers\PurchasePaymentController;
use Illuminate\Support\Facades\Route;

// Authentication routes (public - no middleware)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Redirect root to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


// Upload routes (ID 1, 2 only)
    Route::get('/payments/upload', [PurchasePaymentController::class, 'uploadForm'])->name('payments.upload.form');
    Route::post('/payments/upload', [PurchasePaymentController::class, 'upload'])->name('payments.upload');

// View routes (ID 1, 3 only)
    Route::get('/purchase-letters', [PurchaseLetterController::class, 'index'])->name('purchase_letters.index');
    Route::get('/purchase-letters/chart', [PurchaseLetterController::class, 'chart'])->name('purchase_letters.chart');
    Route::get('/management-report', [ManagementReportController::class, 'index'])->name('management.report');
    Route::get('/payments/view', [PurchasePaymentController::class, 'view'])->name('payments.view');

// Export routes (ID 1, 3 only)
    Route::get('/payments/export', [PurchasePaymentController::class, 'export'])->name('payments.export');
    Route::get('/purchase-letters/export', [PurchaseLetterController::class, 'export'])->name('purchase_letters.export');
    Route::get('management-report/export', [ManagementReportController::class, 'export'])->name('management.report.export');
    Route::get('/export', [DashboardController::class, 'exportFilteredData'])->name('export.filtered');
    Route::get('/export/customers', [DashboardController::class, 'exportTopCustomers'])->name('export.top.customers');
    Route::get('/export/products', [DashboardController::class, 'exportTopProducts'])->name('export.top.products');

// Admin routes (admin and super_admin roles only)
Route::middleware(['auth', 'admin.role'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    
    // Users management (all admins can access)
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/users/{user}/permissions', [AdminController::class, 'editUserPermissions'])
        ->name('users.permissions');
    Route::post('/users/{user}/permissions', [AdminController::class, 'updateUserPermissions'])
        ->name('users.permissions.update');

    // ✅ Projects CRUD (requires group_id = 1)
    // These routes have additional check inside the controller methods
    Route::get('/projects', [AdminController::class, 'projects'])->name('projects');
    Route::get('/projects/create', [AdminController::class, 'createProject'])->name('projects.create');
    Route::post('/projects', [AdminController::class, 'storeProject'])->name('projects.store');
    Route::get('/projects/{project}/edit', [AdminController::class, 'editProject'])->name('projects.edit');
    Route::put('/projects/{project}', [AdminController::class, 'updateProject'])->name('projects.update');
    Route::delete('/projects/{project}', [AdminController::class, 'destroyProject'])->name('projects.destroy');
});