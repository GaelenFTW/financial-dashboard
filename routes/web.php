<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/revenue', [App\Http\Controllers\DashboardController::class, 'revenue'])->name('dashboard.revenue');
