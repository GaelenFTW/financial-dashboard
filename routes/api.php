<?php

use App\Models\User;
use App\Models\MasterProject;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PurchaseLetterController;
use App\Http\Controllers\Api\PurchasePaymentController;
use App\Http\Controllers\Api\ManagementReportController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\RBACController;
use App\Http\Controllers\Api\UserMenuController;
use App\Http\Controllers\Api\DatabaseController;

use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // User Menus (based on RBAC)
    Route::get('/user/menus', [UserMenuController::class, 'index']);
    Route::get('/user/permissions', [UserMenuController::class, 'permissions']);
});

// Dashboard (menu_id: 1)
Route::middleware(['auth:sanctum', 'rbac:1,read'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/sales-trend', [DashboardController::class, 'salesTrend']);
    Route::get('/dashboard/export', [DashboardController::class, 'exportFilteredData']);
    Route::get('/dashboard/export/customers', [DashboardController::class, 'exportTopCustomers']);
    Route::get('/dashboard/export/products', [DashboardController::class, 'exportTopProducts']);
});

// Purchase Letters (menu_id: 4)
Route::middleware('auth:sanctum')->prefix('purchase-letters')->group(function () {
    Route::get('/', [PurchaseLetterController::class, 'index'])->middleware('rbac:4,read');
    Route::get('/chart', [PurchaseLetterController::class, 'chart'])->middleware('rbac:4,read');
    Route::get('/export', [PurchaseLetterController::class, 'export'])->middleware('rbac:4,read');
    Route::get('/{id}', [PurchaseLetterController::class, 'show'])->middleware('rbac:4,read');
    Route::get('/projects/available', [PurchaseLetterController::class, 'getAvailableProjects'])->middleware('rbac:4,read');
});

// Purchase Payments (menu_id: 8 - View, menu_id: 9 - Upload)
Route::middleware('auth:sanctum')->prefix('purchase-payments')->group(function () {
    Route::get('/', [PurchasePaymentController::class, 'view'])->middleware('rbac:8,read');
    Route::get('/export', [PurchasePaymentController::class, 'export'])->middleware('rbac:8,read');
    
    Route::get('/upload-form', [PurchasePaymentController::class, 'uploadForm'])->middleware('rbac:9,read');
    Route::post('/upload', [PurchasePaymentController::class, 'upload'])->middleware('rbac:9,create');
});

// Management Report (menu_id: 5)
Route::middleware(['auth:sanctum', 'rbac:5,read'])->prefix('management-report')->group(function () {
    Route::get('/', [ManagementReportController::class, 'index']);
    Route::get('/export', [ManagementReportController::class, 'export']);
});

// Admin Routes
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->middleware('rbac:2,read');
    
    // Admin Users (menu_id: 7)
    Route::middleware('rbac:7,read')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/create', [AdminController::class, 'createUser'])->middleware('rbac:7,create');
        Route::post('/users', [AdminController::class, 'storeUser'])->middleware('rbac:7,create');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->middleware('rbac:7,update');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->middleware('rbac:7,update');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->middleware('rbac:7,delete');
        
        Route::get('/users/{id}/permissions', [AdminController::class, 'editUserPermissions'])->middleware('rbac:7,update');
        Route::post('/users/{id}/permissions', [AdminController::class, 'updateUserPermissions'])->middleware('rbac:7,update');
    });
    
    // Admin Projects (menu_id: 6)
    Route::middleware('rbac:6,read')->group(function () {
        Route::get('/projects', [AdminController::class, 'projects']);
        Route::get('/projects/create', [AdminController::class, 'createProject'])->middleware('rbac:6,create');
        Route::post('/projects', [AdminController::class, 'storeProject'])->middleware('rbac:6,create');
        Route::get('/projects/{project}/edit', [AdminController::class, 'editProject'])->middleware('rbac:6,update');
        Route::put('/projects/{project}', [AdminController::class, 'updateProject'])->middleware('rbac:6,update');
        Route::delete('/projects/{project}', [AdminController::class, 'destroyProject'])->middleware('rbac:6,delete');
    });
});

// Database Management (menu_id: 10 - to be created)
Route::middleware(['auth:sanctum', 'rbac:2,read'])->prefix('database')->group(function () {
    Route::get('/overview', [DatabaseController::class, 'overview']);
    
    // Groups
    Route::get('/groups', [DatabaseController::class, 'getGroups']);
    Route::post('/groups', [DatabaseController::class, 'storeGroup'])->middleware('rbac:2,create');
    Route::put('/groups/{id}', [DatabaseController::class, 'updateGroup'])->middleware('rbac:2,update');
    Route::delete('/groups/{id}', [DatabaseController::class, 'deleteGroup'])->middleware('rbac:2,delete');
    
    // Menus
    Route::get('/menus', [DatabaseController::class, 'getMenus']);
    Route::post('/menus', [DatabaseController::class, 'storeMenu'])->middleware('rbac:2,create');
    Route::put('/menus/{id}', [DatabaseController::class, 'updateMenu'])->middleware('rbac:2,update');
    Route::delete('/menus/{id}', [DatabaseController::class, 'deleteMenu'])->middleware('rbac:2,delete');
    
    // Actions
    Route::get('/actions', [DatabaseController::class, 'getActions']);
    Route::post('/actions', [DatabaseController::class, 'storeAction'])->middleware('rbac:2,create');
    Route::put('/actions/{id}', [DatabaseController::class, 'updateAction'])->middleware('rbac:2,update');
    Route::delete('/actions/{id}', [DatabaseController::class, 'deleteAction'])->middleware('rbac:2,delete');
});

// RBAC Management
Route::middleware(['auth:sanctum', 'rbac:2,update'])->prefix('rbac')->group(function () {
    Route::get('/', [RBACController::class, 'index']);
    Route::post('/update', [RBACController::class, 'update']);
    Route::get('/user-permissions/{userId}', [RBACController::class, 'getUserPermissions']);
});

// System Overview
Route::middleware('auth:sanctum')->get('/system-overview', function () {
    return response()->json([
        'totalUsers' => User::where('active', true)->count(),
        'activeProjects' => MasterProject::count(),
    ]);
});
