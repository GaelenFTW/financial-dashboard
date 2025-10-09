<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseLetterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JWTController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ManagementReportController;
use App\Http\Controllers\ExcelUploadController;
use App\Http\Controllers\PurchasePaymentController;


// Purchase Letters Routes
Route::get('/purchase-letters', [PurchaseLetterController::class, 'index'])->name('purchase_letters.index')->middleware('auth');
Route::get('/purchase-letters/chart', [PurchaseLetterController::class, 'chart'])->name('purchase_letters.chart')->middleware('auth');
Route::get('/purchase-letters/export', [PurchaseLetterController::class, 'export'])->name('purchase_letters.export')->middleware('auth');
Route::get('/purchase-letters/{id}', [PurchaseLetterController::class, 'show'])->name('purchase_letters.show')->middleware('auth');
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->name('register')->middleware('guest');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');


Route::get('/export', [DashboardController::class, 'exportFilteredData'])
    ->name('export.filtered')
    ->middleware('auth');

Route::get('/export/customers', [DashboardController::class, 'exportTopCustomers'])->name('export.top.customers');
Route::get('/export/products', [DashboardController::class, 'exportTopProducts'])->name('export.top.products');

Route::get('/management-report', [ManagementReportController::class, 'index'])->name('management.report')->middleware('auth');
Route::get('management-report/export', [ManagementReportController::class, 'export'])->name('management.report.export');


Route::get('/payments/upload', [PurchasePaymentController::class, 'uploadForm'])->name('payments.upload.form');
Route::post('/payments/upload', [PurchasePaymentController::class, 'upload'])->name('payments.upload')->middleware('auth');
Route::get('/payments/view', [PurchasePaymentController::class, 'view'])->name('payments.view')->middleware('auth');    
Route::get('/payments/export', [PurchasePaymentController::class, 'export'])->name('payments.export');
