<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManagementReportController extends Controller
{
    protected $jwtController;

    public function __construct(JWTController $jwtController)
    {
        $this->jwtController = $jwtController;
    }

    public function index(Request $request)
    {
        // Fetch data
        $rows  = $this->jwtController->fetchData('api2', ['index2.php', 'login.php']);
        $rows3 = $this->jwtController->fetchData('api3', ['escrow.php', 'login.php']);
        $rows4 = $this->jwtController->fetchData('api4', ['target.php', 'login.php']);

        if (isset($rows['error'])) {
            return view('management-report', ['error' => $rows['error']]);
        }

        // month selection
        $currentMonth = (int)$request->input('month', now()->month);

        // helper: robust number parser
        $parseNumber = function ($val) {
            if ($val === null || $val === '') return 0.0;
            $s = trim((string)$val);
            if (preg_match('/^\(.*\)$/', $s)) { $s = '-' . trim($s, '()'); }
            if (strpos($s, '.') !== false && strpos($s, ',') !== false) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } elseif (strpos($s, ',') !== false && strpos($s, '.') === false) {
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
            $s = preg_replace('/[^0-9\.\-]/', '', $s);
            return is_numeric($s) ? (float)$s : 0.0;
        };

        // month maps (field names in API like "Jan_2025_Piutang", "Feb_2025_Payment")
        $monthShortUc = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];
        $monthShortLc = [
            1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
            5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'aug',
            9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec'
        ];

        // Build summary structure dynamically
        $summary = [];
        $totals = [
            'ytd_target' => 0, 'ytd_actual' => 0,
            'less30days' => 0, 'more31days' => 0, 'more61days' => 0, 'more90days' => 0,
            'lebihbayar' => 0, 'harganetto' => 0,
            'paybeforejan2025' => 0, 'ytdbayarmar2025' => 0,
            'collectioncash' => 0, 'collectioninhouse' => 0, 'collectionkpr' => 0,
            'monthly_meeting_target' => 0,
        ];

        // Initialize monthly target/actual keys for totals as well (jan..dec)
        foreach ($monthShortLc as $m) {
            $totals["{$m}_target"] = 0;
            $totals["{$m}_actual"] = 0;
        }

        // Populate summary from $rows (NORMALIZE TYPE HERE)
        foreach ($rows as $row) {
            // Normalize type immediately: trim spaces and uppercase
            $rawType = $row['TypePembelian'] ?? '';
            $type = strtoupper(trim($rawType));

            if ($type === '' || $type === 'UNKNOWN') continue;

            // Ensure type exists (use normalized $type)
            if (!isset($summary[$type])) {
                $summary[$type] = $totals;
            }

            // Monthly fields (Jan..Dec)
            foreach ($monthShortUc as $num => $shortUc) {
                $lc = $monthShortLc[$num]; // 'jan'
                $pField = "{$shortUc}_2025_Piutang";  // ex: Jan_2025_Piutang
                $aField = "{$shortUc}_2025_Payment"; // ex: Jan_2025_Payment

                $tVal = $parseNumber($row[$pField] ?? 0);
                $aVal = $parseNumber($row[$aField] ?? 0);

                $summary[$type]["{$lc}_target"] += $tVal;
                $summary[$type]["{$lc}_actual"] += $aVal;

                // accumulate totals
                $totals["{$lc}_target"] += $tVal;
                $totals["{$lc}_actual"] += $aVal;
            }

            // YTD, aging and extra fields (use normalized $type)
            $summary[$type]['ytd_target'] += $parseNumber($row['YTD_sd_Mar_2025'] ?? 0);
            $summary[$type]['ytd_actual'] += $parseNumber($row['YTD_bayar_Mar_2025'] ?? 0);
            $summary[$type]['less30days'] += $parseNumber($row['dari_1_sampai_30_DP'] ?? 0);
            $summary[$type]['more31days'] += $parseNumber($row['dari_31_sampai_60_DP'] ?? 0);
            $summary[$type]['more61days'] += $parseNumber($row['dari_61_sampai_90_DP'] ?? 0);
            $summary[$type]['more90days'] += $parseNumber($row['diatas_90_DP'] ?? 0);
            $summary[$type]['lebihbayar'] += abs($parseNumber($row['lebih_bayar'] ?? 0));
            $summary[$type]['harganetto'] += $parseNumber($row['harga_netto'] ?? 0);
            $summary[$type]['paybeforejan2025'] += $parseNumber($row['Payment_Before_Jan_2025'] ?? 0);
            $summary[$type]['ytdbayarmar2025'] += $parseNumber($row['YTD_bayar_Mar_2025'] ?? 0);
            $summary[$type]['collectioncash'] += $parseNumber($row['collection_target_cash_v'] ?? 0);
            $summary[$type]['collectioninhouse'] += $parseNumber($row['collection_target_inhouse_v'] ?? 0);
            $summary[$type]['collectionkpr'] += $parseNumber($row['collection_target_kpr_v'] ?? 0);

            // accumulate totals for these fields too
            $totals['ytd_target'] += $parseNumber($row['YTD_sd_Mar_2025'] ?? 0);
            $totals['ytd_actual'] += $parseNumber($row['YTD_bayar_Mar_2025'] ?? 0);
            $totals['less30days'] += $parseNumber($row['dari_1_sampai_30_DP'] ?? 0);
            $totals['more31days'] += $parseNumber($row['dari_31_sampai_60_DP'] ?? 0);
            $totals['more61days'] += $parseNumber($row['dari_61_sampai_90_DP'] ?? 0);
            $totals['more90days'] += $parseNumber($row['diatas_90_DP'] ?? 0);
            $totals['lebihbayar'] += abs($parseNumber($row['lebih_bayar'] ?? 0));
            $totals['harganetto'] += $parseNumber($row['harga_netto'] ?? 0);
            $totals['paybeforejan2025'] += $parseNumber($row['Payment_Before_Jan_2025'] ?? 0);
            $totals['ytdbayarmar2025'] += $parseNumber($row['YTD_bayar_Mar_2025'] ?? 0);
            $totals['collectioncash'] += $parseNumber($row['collection_target_cash_v'] ?? 0);
            $totals['collectioninhouse'] += $parseNumber($row['collection_target_inhouse_v'] ?? 0);
            $totals['collectionkpr'] += $parseNumber($row['collection_target_kpr_v'] ?? 0);
        }

        // put totals into summary (TOTAL key stays uppercase)
        $summary['TOTAL'] = $totals;

        // ESCROW & OUTSTANDING building (unchanged logic)
        $escrowTotals = [];
        $outstanding = [];
        $outstandingTotalsCalc = ['jatuh_tempo' => 0, 'belum_jatuh_tempo' => 0, 'total' => 0];

        if (is_array($rows3) && count($rows3) > 0) {
            foreach ($rows3 as $r) {
                $t = $parseNumber($r['Total'] ?? $r['total'] ?? 0);
                $hutang = $parseNumber($r['Hutang_Yang_Belum_Jatuh_Tempo'] ?? 0);
                $escrowTotals[] = ['total' => $t, 'hutang' => $hutang];

                $outstanding['ESCROW']['jatuh_tempo'] = ($outstanding['ESCROW']['jatuh_tempo'] ?? 0) + $t;
                $outstanding['ESCROW']['belum_jatuh_tempo'] = ($outstanding['ESCROW']['belum_jatuh_tempo'] ?? 0) + $hutang;
                $outstanding['ESCROW']['total'] = ($outstanding['ESCROW']['total'] ?? 0) + ($t + $hutang);

                $outstandingTotalsCalc['jatuh_tempo'] += $t;
                $outstandingTotalsCalc['belum_jatuh_tempo'] += $hutang;
                $outstandingTotalsCalc['total'] += ($t + $hutang);
            }
        }

        // AGING -> from summary['TOTAL']
        $agingTotal = (
            ($summary['TOTAL']['less30days'] ?? 0) +
            ($summary['TOTAL']['more31days'] ?? 0) +
            ($summary['TOTAL']['more61days'] ?? 0) +
            ($summary['TOTAL']['more90days'] ?? 0)
        );

        $belumAging = (
            ($summary['TOTAL']['harganetto'] ?? 0) -
            ($summary['TOTAL']['paybeforejan2025'] ?? 0) -
            ($summary['TOTAL']['ytdbayarmar2025'] ?? 0) -
            $agingTotal
        );

        $outstanding['AGING'] = [
            'jatuh_tempo' => $agingTotal,
            'belum_jatuh_tempo' => $belumAging,
            'total' => $agingTotal + $belumAging,
        ];

        $outstandingTotalsCalc['jatuh_tempo'] += $agingTotal;
        $outstandingTotalsCalc['belum_jatuh_tempo'] += $belumAging;
        $outstandingTotalsCalc['total'] += ($agingTotal + $belumAging);

        // TOTAL outstanding
        $outstanding['TOTAL'] = $outstandingTotalsCalc;

        // collectionTargets from rows4 (monthly meeting targets) - AGGREGATE rows per month
        $collectionTargets = [];
        if (is_array($rows4)) {
            foreach ($rows4 as $r) {
                $month = (int)($r['bulan'] ?? 0);
                if ($month > 0) {
                    if (!isset($collectionTargets[$month])) {
                        $collectionTargets[$month] = ['cash' => 0, 'inhouse' => 0, 'kpr' => 0];
                    }
                    $collectionTargets[$month]['cash'] += $parseNumber($r['collection_target_cash_v'] ?? 0);
                    $collectionTargets[$month]['inhouse'] += $parseNumber($r['collection_target_inhouse_v'] ?? 0);
                    $collectionTargets[$month]['kpr'] += $parseNumber($r['collection_target_kpr_v'] ?? 0);
                }
            }
        }

        // inject meeting targets into summary per-type (use normalized keys: CASH/INHOUSE/KPR)
        foreach ($summary as $typeKey => &$data) {
            $cleanType = strtoupper(trim($typeKey)); // safe guard
            $data['monthly_meeting_target'] = 0;

            if ($cleanType === 'CASH') {
                $data['monthly_meeting_target'] = $collectionTargets[$currentMonth]['cash'] ?? 0;
            } elseif ($cleanType === 'INHOUSE') {
                $data['monthly_meeting_target'] = $collectionTargets[$currentMonth]['inhouse'] ?? 0;
            } elseif ($cleanType === 'KPR') {
                $data['monthly_meeting_target'] = $collectionTargets[$currentMonth]['kpr'] ?? 0;
            }
        }
        unset($data);

        // TOTAL meeting target = sum of the three
        $summary['TOTAL']['monthly_meeting_target'] = (
            ($collectionTargets[$currentMonth]['cash'] ?? 0) +
            ($collectionTargets[$currentMonth]['inhouse'] ?? 0) +
            ($collectionTargets[$currentMonth]['kpr'] ?? 0)
        );

        // Build presentation arrays for Blade ---------------------------------

        // ordering: CASH, INHOUSE, KPR, then any other types, then TOTAL
        $preferred = ['CASH', 'INHOUSE', 'KPR'];
        $types = [];
        foreach ($preferred as $p) {
            if (isset($summary[$p])) $types[] = $p;
        }
        foreach (array_keys($summary) as $k) {
            if ($k === 'TOTAL' || in_array($k, $types)) continue;
            $types[] = $k;
        }
        if (isset($summary['TOTAL'])) $types[] = 'TOTAL';

        $monthKeyLc = $monthShortLc[$currentMonth] ?? 'jan';

        // monthlyPerformance rows and totals
        $monthlyPerformance = [];
        $monthlyTotalsCalc = ['meeting_target' => 0, 'sales_target' => 0, 'actual' => 0];

        foreach ($types as $type) {
            // ensure type aligns with summary keys
            $type = strtoupper(trim($type));

            $meetingTarget = $summary[$type]['monthly_meeting_target'] ?? 0;
            $salesTarget   = $summary[$type]["{$monthKeyLc}_target"] ?? 0;
            $actual        = $summary[$type]["{$monthKeyLc}_actual"] ?? 0;
            $pct           = $salesTarget > 0 ? round(($actual / $salesTarget) * 100, 1) : 0.0;
            $status        = $pct >= 100 ? 'ACHIEVED' : ($pct >= 80 ? 'ON TRACK' : 'BELOW TARGET');

            $monthlyPerformance[] = [
                'payment'        => $type,
                'meeting_target' => $meetingTarget,
                'sales_target'   => $salesTarget,
                'actual'         => $actual,
                'percentage'     => $pct,
                'status'         => $status,
            ];

            if ($type !== 'TOTAL') {
                $monthlyTotalsCalc['meeting_target'] += $meetingTarget;
                $monthlyTotalsCalc['sales_target']   += $salesTarget;
                $monthlyTotalsCalc['actual']         += $actual;
            }
        }

        $monthlyTotalsCalc['percentage'] = $monthlyTotalsCalc['sales_target'] > 0
            ? round(($monthlyTotalsCalc['actual'] / $monthlyTotalsCalc['sales_target']) * 100, 1)
            : 0.0;
        $monthlyTotalsCalc['status'] = $monthlyTotalsCalc['percentage'] >= 100 ? 'ACHIEVED' : ($monthlyTotalsCalc['percentage'] >= 80 ? 'ON TRACK' : 'BELOW TARGET');

        // YTD Performance rows and totals
        $ytdPerformance = [];
        $ytdTotalsCalc = ['meeting_target' => 'upcoming', 'sales_target' => 0, 'actual' => 0];
        foreach ($types as $type) {
            $type = strtoupper(trim($type));
            $ytdSalesTarget = $summary[$type]['ytd_target'] ?? 0;
            $ytdActual = $summary[$type]['ytd_actual'] ?? 0;
            $pct = $ytdSalesTarget > 0 ? round(($ytdActual / $ytdSalesTarget) * 100, 1) : 0.0;
            $status = $pct >= 100 ? 'ACHIEVED' : ($pct >= 80 ? 'ON TRACK' : 'BELOW TARGET');

            $ytdPerformance[] = [
                'payment' => $type,
                'meeting_target' => 'upcoming',
                'sales_target' => $ytdSalesTarget,
                'actual' => $ytdActual,
                'percentage' => $pct,
                'status' => $status,
            ];

            if ($type !== 'TOTAL') {
                $ytdTotalsCalc['sales_target'] += $ytdSalesTarget;
                $ytdTotalsCalc['actual'] += $ytdActual;
            }
        }
        $ytdTotalsCalc['percentage'] = $ytdTotalsCalc['sales_target'] > 0 ? round(($ytdTotalsCalc['actual'] / $ytdTotalsCalc['sales_target']) * 100, 1) : 0.0;
        $ytdTotalsCalc['status'] = $ytdTotalsCalc['percentage'] >= 100 ? 'ACHIEVED' : ($ytdTotalsCalc['percentage'] >= 80 ? 'ON TRACK' : 'BELOW TARGET');

        // AGING rows and totals
        $aging = [];
        $agingTotals = ['lt30' => 0, 'd30_60' => 0, 'd60_90' => 0, 'gt90' => 0, 'lebih_bayar' => 0];
        foreach ($types as $type) {
            $type = strtoupper(trim($type));
            if ($type === 'TOTAL') continue;
            $lt30 = $summary[$type]['less30days'] ?? 0;
            $d30_60 = $summary[$type]['more31days'] ?? 0;
            $d60_90 = $summary[$type]['more61days'] ?? 0;
            $gt90 = $summary[$type]['more90days'] ?? 0;
            $lebih = $summary[$type]['lebihbayar'] ?? 0;

            $aging[] = [
                'payment' => $type,
                'lt30' => $lt30,
                'd30_60' => $d30_60,
                'd60_90' => $d60_90,
                'gt90' => $gt90,
                'lebih_bayar' => $lebih,
            ];

            $agingTotals['lt30'] += $lt30;
            $agingTotals['d30_60'] += $d30_60;
            $agingTotals['d60_90'] += $d60_90;
            $agingTotals['gt90'] += $gt90;
            $agingTotals['lebih_bayar'] += $lebih;
        }

        // OUTSTANDING rows (ESCROW, AGING) -> transform outstanding assoc to rows
        $outstandingRows = [];
        $grandTotalOutstanding = $outstanding['TOTAL']['total'] ?? 0;
        foreach ($outstanding as $k => $vals) {
            if ($k === 'TOTAL') continue;
            $sum = $vals['total'] ?? 0;
            $pct = $grandTotalOutstanding > 0 ? round(($sum / $grandTotalOutstanding) * 100, 1) : 0.0;
            $outstandingRows[] = [
                'type' => $k,
                'jatuh_tempo' => $vals['jatuh_tempo'] ?? 0,
                'belum_jatuh_tempo' => $vals['belum_jatuh_tempo'] ?? 0,
                'total' => $sum,
                'percentage' => $pct,
            ];
        }
        $outstandingTotals = [
            'jatuh_tempo' => $outstanding['TOTAL']['jatuh_tempo'] ?? 0,
            'belum_jatuh_tempo' => $outstanding['TOTAL']['belum_jatuh_tempo'] ?? 0,
            'total' => $outstanding['TOTAL']['total'] ?? 0,
            'percentage' => 100,
        ];

        if (empty($escrowTotals)) {
            $escrowTotals = [['total' => 0, 'hutang' => 0]];
        }

        // Pass everything to Blade
        return view('management-report', [
            'summary' => $summary,
            'rows' => $rows,
            'collectionTargets' => $collectionTargets,
            'escrowTotals' => $escrowTotals,
            'monthlyPerformance' => $monthlyPerformance,
            'monthlyTotals' => $monthlyTotalsCalc,
            'ytdPerformance' => $ytdPerformance,
            'ytdTotals' => $ytdTotalsCalc,
            'aging' => $aging,
            'agingTotals' => $agingTotals,
            'outstanding' => $outstandingRows,
            'outstandingTotals' => $outstandingTotals,
            'currentMonth' => $currentMonth,
            'months' => $monthShortLc,
        ]);
    }
}
