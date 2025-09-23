<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseLetterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JWTController;
use App\Http\Controllers\AuthController;

// Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/purchase-letters', [PurchaseLetterController::class, 'index'])->middleware('auth')->name('purchase_letters');
Route::get('/purchase-letters/charts', [PurchaseLetterController::class, 'chart'])->middleware('auth')->name('purchase_letters/chart');
Route::get('/', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->name('register')->middleware('guest');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');


Route::get('/export', [DashboardController::class, 'exportFilteredData'])
    ->name('export.filtered')
    ->middleware('auth');
