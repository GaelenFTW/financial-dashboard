<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManagementReportController extends Controller
{
    public function index(Request $request)
    {
        // --- 1. Get selected month & year (defaults to current) ---
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $monthNames = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec',
        ];

        $monthKey = $monthNames[$month];

        // --- 2. Define column names ---
        $piutangCol   = "{$monthKey}_Year_Piutang";
        $paymentCol   = "{$monthKey}_Year_Payment";
        $ytdTargetCol = "YTD_sd_Year";
        $ytdActualCol = "YTD_bayar_Year";

        Log::info("📊 Columns used:", [
            'piutang' => $piutangCol,
            'payment' => $paymentCol,
            'ytd_target' => $ytdTargetCol,
            'ytd_actual' => $ytdActualCol,
        ]);

        try {
            // --- 3. Monthly Performance ---
            $monthlyPerformance = DB::table('purchase_payments')
                ->select(
                    'TypePembelian',
                    DB::raw("SUM(COALESCE(CAST([$piutangCol] AS DECIMAL(18,2)), 0)) as target"),
                    DB::raw("SUM(COALESCE(CAST([$paymentCol] AS DECIMAL(18,2)), 0)) as actual")
                )
                ->whereNotNull('TypePembelian')
                ->groupBy('TypePembelian')
                ->get()
                ->map(function ($row) {
                    $row->achievement = $row->target > 0 ? round(($row->actual / $row->target) * 100, 1) : 0;
                    return $row;
                });

            // --- 4. YTD Performance ---
            $ytdPerformance = DB::table('purchase_payments')
                ->select(
                    'TypePembelian',
                    DB::raw("SUM(COALESCE(CAST([$ytdTargetCol] AS DECIMAL(18,2)), 0)) as target"),
                    DB::raw("SUM(COALESCE(CAST([$ytdActualCol] AS DECIMAL(18,2)), 0)) as actual")
                )
                ->whereNotNull('TypePembelian')
                ->groupBy('TypePembelian')
                ->get()
                ->map(function ($row) {
                    $row->achievement = $row->target > 0 ? round(($row->actual / $row->target) * 100, 1) : 0;
                    return $row;
                });

            // --- 5. Calculate totals for summary cards ---
            $monthlyTotals = [
                'target' => $monthlyPerformance->sum('target'),
                'actual' => $monthlyPerformance->sum('actual'),
            ];
            $monthlyTotals['percentage'] = $monthlyTotals['target'] > 0
                ? round(($monthlyTotals['actual'] / $monthlyTotals['target']) * 100, 1)
                : 0;
            $monthlyTotals['status'] = $monthlyTotals['percentage'] >= 100 ? 'ACHIEVED' : 'ON TRACK';

            $ytdTotals = [
                'target' => $ytdPerformance->sum('target'),
                'actual' => $ytdPerformance->sum('actual'),
            ];
            $ytdTotals['percentage'] = $ytdTotals['target'] > 0
                ? round(($ytdTotals['actual'] / $ytdTotals['target']) * 100, 1)
                : 0;
            $ytdTotals['status'] = $ytdTotals['percentage'] >= 100 ? 'ACHIEVED' : 'ON TRACK';

            // --- 6. Dummy placeholders for Aging & Outstanding ---
            $aging = collect();
            $agingTotals = ['lt30'=>0,'d30_60'=>0,'d60_90'=>0,'gt90'=>0,'lebih_bayar'=>0];
            $outstanding = collect();
            $outstandingTotals = ['jatuh_tempo'=>0,'belum_jatuh_tempo'=>0,'total'=>0,'percentage'=>0];

        } catch (\Exception $e) {
            Log::error("❌ SQL Error: " . $e->getMessage());
            return response()->view('management-report', [
                'month' => $month,
                'year' => $year,
                'monthlyPerformance' => collect(),
                'ytdPerformance' => collect(),
                'monthNames' => $monthNames,
                'monthlyTotals' => ['percentage'=>0,'status'=>'-'],
                'ytdTotals' => ['percentage'=>0,'status'=>'-'],
                'aging' => collect(),
                'agingTotals' => $agingTotals,
                'outstanding' => collect(),
                'outstandingTotals' => $outstandingTotals,
                'error' => $e->getMessage(),
            ]);
        }

        return view('management-report', compact(
            'month','year','monthNames',
            'monthlyPerformance','ytdPerformance',
            'monthlyTotals','ytdTotals',
            'aging','agingTotals','outstanding','outstandingTotals'
        ));
    }
}
