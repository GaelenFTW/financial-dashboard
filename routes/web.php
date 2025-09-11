<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseLetterController;

Route::get('/purchase-letters', [PurchaseLetterController::class, 'index']);
