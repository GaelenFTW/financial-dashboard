<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseLetterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JWTController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ManagementReportController;
use App\Http\Controllers\ExcelUploadController;
use App\Http\Controllers\PurchasePaymentController;
use App\Http\Controllers\Admin\UserManagementController;


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

// Upload routes (ID 1, 2 only)
Route::middleware(['auth', 'user.permission:upload'])->group(function () {
    Route::get('/payments/upload', [PurchasePaymentController::class, 'uploadForm'])->name('payments.upload.form');
    Route::post('/payments/upload', [PurchasePaymentController::class, 'upload'])->name('payments.upload');
});

// View routes (ID 1, 3 only)  
Route::middleware(['auth', 'user.permission:view'])->group(function () {
    Route::get('/purchase-letters', [PurchaseLetterController::class, 'index'])->name('purchase_letters.index');
    Route::get('/purchase-letters/chart', [PurchaseLetterController::class, 'chart'])->name('purchase_letters.chart');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/management-report', [ManagementReportController::class, 'index'])->name('management.report');
    Route::get('/payments/view', [PurchasePaymentController::class, 'view'])->name('payments.view');
});

// Export routes (ID 1, 3 only)
Route::middleware(['auth', 'user.permission:export'])->group(function () {
    Route::get('/payments/export', [PurchasePaymentController::class, 'export'])->name('payments.export');
    Route::get('/purchase-letters/export', [PurchaseLetterController::class, 'export'])->name('purchase_letters.export');
    Route::get('management-report/export', [ManagementReportController::class, 'export'])->name('management.report.export');
    Route::get('/export', [DashboardController::class, 'exportFilteredData'])->name('export.filtered');
    Route::get('/export/customers', [DashboardController::class, 'exportTopCustomers'])->name('export.top.customers');
    Route::get('/export/products', [DashboardController::class, 'exportTopProducts'])->name('export.top.products');
});

//admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', 'AdminController@dashboard')->name('dashboard');
    
    // Users Management
    Route::get('/users', 'AdminController@usersIndex')->name('users.index');
    Route::get('/users/{user}/edit', 'AdminController@usersEdit')->name('users.edit');
    Route::put('/users/{user}', 'AdminController@usersUpdate')->name('users.update');
    Route::get('/users/{user}/projects', 'AdminController@userProjects')->name('users.projects');
    Route::post('/users/{user}/projects', 'AdminController@updateUserProjects')->name('users.projects.update');
    
    // Projects Management
    Route::get('/projects', 'AdminController@projectsIndex')->name('projects.index');
    Route::get('/projects/create', 'AdminController@projectsCreate')->name('projects.create');
    Route::post('/projects', 'AdminController@projectsStore')->name('projects.store');
    Route::get('/projects/{project}/edit', 'AdminController@projectsEdit')->name('projects.edit');
    Route::put('/projects/{project}', 'AdminController@projectsUpdate')->name('projects.update');
    Route::delete('/projects/{project}', 'AdminController@projectsDestroy')->name('projects.destroy');
});