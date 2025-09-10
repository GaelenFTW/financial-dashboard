<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $summary = DB::select('EXEC sp_get_invoice_summary');
        $totalPaid = $summary[0]->total_paid ?? 0;
        $totalOverdue = $summary[0]->total_overdue ?? 0;
        $totalOpen = $summary[0]->total_open ?? 0;

        $monthly = DB::select('EXEC sp_get_invoice_monthly');

        $invoices = DB::table('invoices')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact('totalPaid', 'totalOverdue', 'totalOpen', 'monthly', 'invoices'));
    }

    public function revenue()
    {
    // dummy data
    $customers = [
        ['name' => 'Elite Systems', 'revenue' => 1000],
        ['name' => 'Stellar Innovations', 'revenue' => 1000],
        ['name' => 'Innovate Systems', 'revenue' => 1200],
        ['name' => 'Bright Future Ltd.', 'revenue' => 1500],
        ['name' => 'Innovate Industries', 'revenue' => 2594],
        ['name' => 'Proactive Solutions', 'revenue' => 3831],
    ];

    $products = [
        ['name' => 'Enterprise', 'avg_price' => 2552.94, 'qty' => 17, 'amount' => 43400],
        ['name' => 'DA Solutions', 'avg_price' => 3327.50, 'qty' => 10, 'amount' => 33275],
        ['name' => 'Business', 'avg_price' => 570.00, 'qty' => 50, 'amount' => 28500],
        ['name' => 'Standart', 'avg_price' => 1616.00, 'qty' => 15, 'amount' => 24240],
        ['name' => 'Web design', 'avg_price' => 2400.00, 'qty' => 10, 'amount' => 24000],
        ['name' => 'Alpha', 'avg_price' => 937.50, 'qty' => 20, 'amount' => 18750],
    ];

    return view('revenue', compact('customers', 'products'));
    }


}
