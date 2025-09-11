<?php

namespace App\Http\Controllers;

use App\Models\PurchaseLetter;

class PurchaseLetterController extends Controller
{
    public function index()
    {
        // use PurchaseDate to order since you don't have created_at
        $letters = PurchaseLetter::orderBy('PurchaseDate', 'desc')->paginate(20);

        return view('purchase_letters.index', compact('letters'));
    }
}
