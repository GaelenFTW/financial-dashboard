<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function overview(Request $req)
    {
        // KPIs
        $totalCustomers = Customer::count();
        $totalInvoices = Invoice::count();
        $totalPaid = Invoice::sum(DB::raw('amount - balance'));
        $totalOutstanding = Invoice::sum('balance');

        // monthly payments (group by yyyy-mm)
        $monthly = Invoice::selectRaw("FORMAT(invoice_date, 'yyyy-MM') as month, SUM(amount - balance) as paid, SUM(balance) as open_amount")
            ->whereNotNull('invoice_date')
            ->groupByRaw("FORMAT(invoice_date, 'yyyy-MM')")
            ->orderByRaw("FORMAT(invoice_date, 'yyyy-MM')")
            ->get();

        // latest payments/invoices for table
        $latestInvoices = Invoice::with('customer')->orderByDesc('invoice_date')->limit(20)->get();

        return view('dash.overview', compact(
            'totalCustomers','totalInvoices','totalPaid','totalOutstanding','monthly','latestInvoices'
        ));
    }

    public function customers()
    {
        $topCustomers = Customer::withSum('invoices','amount')
            ->orderByDesc('invoices_sum_amount')
            ->limit(10)
            ->get();

        $customers = Customer::with('invoices')->get();

        return view('dash.customers', compact('topCustomers','customers'));
    }

    public function aging()
    {
        // Build buckets from invoice due_date vs today
        $today = Carbon::today();
        $aging = Invoice::selectRaw("
            SUM(CASE WHEN DATEDIFF(day,due_date,GETDATE()) BETWEEN 1 AND 30 THEN balance ELSE 0 END) as d1_30,
            SUM(CASE WHEN DATEDIFF(day,due_date,GETDATE()) BETWEEN 31 AND 60 THEN balance ELSE 0 END) as d31_60,
            SUM(CASE WHEN DATEDIFF(day,due_date,GETDATE()) BETWEEN 61 AND 90 THEN balance ELSE 0 END) as d61_90,
            SUM(CASE WHEN DATEDIFF(day,due_date,GETDATE()) > 90 THEN balance ELSE 0 END) as d90p
        ")->first();

        $overdueList = Invoice::with('customer')->where('due_date','<', $today)->orderByDesc('balance')->get();

        return view('dash.aging', compact('aging','overdueList'));
    }

    public function cashflow()
    {
        // Basic cashflow: monthly totals (paid vs outstanding)
        $monthly = Invoice::selectRaw("FORMAT(invoice_date, 'yyyy-MM') as month, SUM(amount - balance) as paid, SUM(balance) as outstanding")
            ->whereNotNull('invoice_date')
            ->groupByRaw("FORMAT(invoice_date, 'yyyy-MM')")
            ->orderByRaw("FORMAT(invoice_date, 'yyyy-MM')")
            ->get();

        return view('dash.cashflow', compact('monthly'));
    }
}
