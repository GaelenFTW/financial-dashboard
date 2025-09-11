<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class,'overview'])->name('dashboard.overview');
Route::get('/customers', [DashboardController::class,'customers'])->name('dashboard.customers');
Route::get('/aging', [DashboardController::class,'aging'])->name('dashboard.aging');
Route::get('/cashflow', [DashboardController::class,'cashflow'])->name('dashboard.cashflow');
