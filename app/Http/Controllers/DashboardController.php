<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
public function index()
    {
        // Call summary SP
        $summary = DB::select('EXEC sp_get_invoice_summary');
        $totalPaid = $summary[0]->total_paid ?? 0;
        $totalOverdue = $summary[0]->total_overdue ?? 0;
        $totalOpen = $summary[0]->total_open ?? 0;

        // Call monthly SP
        $monthly = DB::select('EXEC sp_get_invoice_monthly');

        // Ambil semua invoices (misalnya 10 terbaru)
        $invoices = DB::table('invoices')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact('totalPaid', 'totalOverdue', 'totalOpen', 'monthly', 'invoices'));
    }

}
