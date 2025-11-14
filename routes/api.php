<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PurchaseLetterController;
use App\Http\Controllers\Api\PurchasePaymentController;
use App\Http\Controllers\Api\ManagementReportController;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Needs Sanctum Token)
|--------------------------------------------------------------------------
// */
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Payments (protected)
Route::get('/purchase-payments/upload-form', [PurchasePaymentController::class, 'uploadForm']);
    Route::get('/purchase-payments', [PurchasePaymentController::class, 'view']);
    Route::get('/purchase-payments/export', [PurchasePaymentController::class, 'export']);
    Route::post('/purchase-payments/upload', [PurchasePaymentController::class, 'upload']);
});

/*
|--------------------------------------------------------------------------
| Public Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/dashboard/export', [DashboardController::class, 'exportFilteredData']);
Route::get('/dashboard/export/customers', [DashboardController::class, 'exportTopCustomers']);
Route::get('/dashboard/export/products', [DashboardController::class, 'exportTopProducts']);

/*
|--------------------------------------------------------------------------
| Public Purchase Letters
|--------------------------------------------------------------------------
*/
Route::get('/purchase-letters', [PurchaseLetterController::class, 'index']);
Route::get('/purchase-letters/chart', [PurchaseLetterController::class, 'chart']);
Route::get('/purchase-letters/export', [PurchaseLetterController::class, 'export']);
Route::get('/purchase-letters/{id}', [PurchaseLetterController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Public Management Report
|--------------------------------------------------------------------------
*/
Route::get('/management-report', [ManagementReportController::class, 'index']);
Route::get('/management-report/export', [ManagementReportController::class, 'export']);


// routes/api.php
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index']);
    
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/users/create', [AdminController::class, 'createUser']);
    Route::post('/users', [AdminController::class, 'storeUser']);
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser']);
    Route::put('/users/{user}', [AdminController::class, 'updateUser']);
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser']);
    Route::get('/users/{id}/permissions', [AdminController::class, 'editUserPermissions']);
    Route::post('/users/{id}/permissions', [AdminController::class, 'updateUserPermissions']);
    
    Route::get('/projects', [AdminController::class, 'projects']);
    Route::get('/projects/create', [AdminController::class, 'createProject']);
    Route::post('/projects', [AdminController::class, 'storeProject']);
    Route::get('/projects/{project}/edit', [AdminController::class, 'editProject']);
    Route::put('/projects/{project}', [AdminController::class, 'updateProject']);
    Route::delete('/projects/{project}', [AdminController::class, 'destroyProject']);
});