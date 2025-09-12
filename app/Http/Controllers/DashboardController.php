<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $table = 'Worksheet$';

        // Top 10 Customers by Revenue
        $customers = DB::table($table)
            ->select('CustomerName', DB::raw('SUM(CAST(HrgJualTotal AS FLOAT)) as revenue'))
            ->groupBy('CustomerName')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // Top 10 Products by Revenue
        $products = DB::table($table)
            ->select('type_unit as ProductName', DB::raw('SUM(CAST(HrgJualTotal AS FLOAT)) as revenue'))
            ->groupBy('type_unit')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // Aggregates
        $totalRevenue = DB::table($table)
            ->select(DB::raw('SUM(CAST(HrgJualTotal AS FLOAT)) as total'))
            ->value('total');
        $numCustomers = DB::table($table)->distinct('CustomerName')->count('CustomerName');
        $productsSold = DB::table($table)->count('type_unit');
        $avgRevenue = DB::table($table)
            ->select(DB::raw('AVG(CAST(HrgJualTotal AS FLOAT)) as avg'))
            ->value('avg');


        return view('dashboard', [
            'customers'    => $customers,
            'products'     => $products,
            'totalRevenue' => $totalRevenue,
            'numCustomers' => $numCustomers,
            'productsSold' => $productsSold,
            'avgRevenue'   => $avgRevenue,
        ]);
    }
}
