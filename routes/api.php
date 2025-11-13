<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PurchaseLetterController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    

});

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/dashboard/export', [DashboardController::class, 'exportFilteredData']);
Route::get('/dashboard/export/customers', [DashboardController::class, 'exportTopCustomers']);
Route::get('/dashboard/export/products', [DashboardController::class, 'exportTopProducts']);

Route::get('/purchase-letters', [PurchaseLetterController::class, 'index']);
Route::get('/purchase-letters/chart', [PurchaseLetterController::class, 'chart']);
Route::get('/purchase-letters/export', [PurchaseLetterController::class, 'export']);
Route::get('/purchase-letters/{id}', [PurchaseLetterController::class, 'show']);

Route::get('/payments', [PurchasePaymentController::class, 'view']);
Route::get('/payments/export', [PurchasePaymentController::class, 'export']);