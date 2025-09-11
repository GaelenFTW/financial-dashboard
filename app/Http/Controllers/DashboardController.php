<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- ADD THIS

class DashboardController extends Controller
{
public function index()
{
    $topCustomers = DB::table('purchase_letters')
        ->select('customer_name', DB::raw('SUM(hrg_jual_total) as revenue'))
        ->groupBy('customer_name')
        ->orderByDesc('revenue')
        ->take(10)
        ->get();

    $customers = $topCustomers; // reuse

    $topProducts = DB::table('purchase_letters')
        ->select('type_unit as ProductName', DB::raw('SUM(hrg_jual_total) as revenue'))
        ->groupBy('type_unit')
        ->orderByDesc('revenue')
        ->take(10)
        ->get();

    $totalRevenue  = DB::table('purchase_letters')->sum('hrg_jual_total');
    $numCustomers  = DB::table('purchase_letters')->distinct('customer_name')->count('customer_name');
    $numProducts   = DB::table('purchase_letters')->count('type_unit');
    $avgRevenue    = DB::table('purchase_letters')->avg('hrg_jual_total');
    $productsSold  = DB::table('purchase_letters')->count();
    $avgPrice      = DB::table('purchase_letters')->avg('hrg_jual_total');

    // For charts
    $customerNames   = $topCustomers->pluck('customer_name');
    $customerRevenue = $topCustomers->pluck('revenue');
    $productNames    = $topProducts->pluck('ProductName');
    $productRevenue  = $topProducts->pluck('revenue');

    return view('dashboard', compact(
        'topCustomers', 'topProducts', 'totalRevenue', 'numCustomers',
        'numProducts', 'avgRevenue', 'productsSold', 'customers', 'avgPrice',
        'customerNames', 'customerRevenue', 'productNames', 'productRevenue'
    ));
}


}
