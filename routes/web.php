<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseLetterController;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/purchase-letters', [PurchaseLetterController::class, 'index']);
Route::get('/purchase-letters/charts', [PurchaseLetterController::class, 'chart']);
