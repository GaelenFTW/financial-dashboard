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

    protected function getData(): array
    {
        $rows = $this->jwtController->fetchData2();
        return is_array($rows) ? $rows : [];
    }

    protected function getData3(): array
    {
        $rows3 = $this->jwtController->fetchData3();
        return is_array($rows3) ? $rows3 : [];
    }

    protected function getData4(): array
    {
        $rows4 = $this->jwtController->fetchData4();
        return is_array($rows4) ? $rows4 : [];
    }


    public function index(Request $request)
    {

    $rows  = $this->getData();
    $rows3 = $this->getData3();
    $rows4 = $this->getData4();
    dd($rows4);

    if (isset($rows['error'])) {
        return view('management-report', ['error' => $rows['error']]);
    }

    $currentMonth = $request->input('month', now()->month);


    // ---------- helper to parse numbers robustly ----------
    $parseNumber = function($val) {
        if ($val === null || $val === '') return 0.0;
        $s = (string) $val;
        $s = trim($s);

        if (preg_match('/^\(.*\)$/', $s)) {
            $s = '-' . trim($s, '()');
        }
        $s = str_replace(',', '', $s);

        if (strpos($s, '.') !== false && strpos($s, ',') !== false) {
            $s = str_replace('.', '', $s);    
            $s = str_replace(',', '.', $s);   
        } elseif (strpos($s, ',') !== false && strpos($s, '.') === false) {
            $s = str_replace(',', '.', $s);
        }

        $s = preg_replace('/[^0-9\.\-]/', '', $s);

        return is_numeric($s) ? (float) $s : 0.0;
    };


    // === SALES SUMMARY (Monthly + YTD) ===
    $summary = [];
    $totals = [
        'mar_target' => 0,
        'mar_actual' => 0,
        'ytd_target'     => 0,
        'ytd_actual'     => 0,
        'less30days'     => 0,
        'more31days'     => 0,
        'more61days'     => 0,
        'more90days'     => 0,
        'lebihbayar'     => 0,
        'harganetto'     => 0,
        'paybeforejan2025' => 0,
        'ytdbayarmar2025' => 0,
        'collectioncash' => 0,
        'collectioninhouse' => 0,
        'collectionkpr' => 0,
        'total_meeting_target' => 0,
        'monthly_meeting_target' => 0,
    ];

    foreach ($rows as $row) {
        $type = $row['TypePembelian'] ?? null;
        if (empty($type) || $type === 'UNKNOWN') {
            continue;
        }


        $jantarget = $parseNumber($row['Jan_2025_Piutang'] ?? 0);
        $janactual = $parseNumber($row['Jan_2025_Payment'] ?? 0);
        $febtarget = $parseNumber($row['Feb_2025_Piutang'] ?? 0);
        $febactual = $parseNumber($row['Feb_2025_Payment'] ?? 0);
        $martarget = $parseNumber($row['Mar_2025_Piutang'] ?? 0);
        $maractual = $parseNumber($row['Mar_2025_Payment'] ?? 0);
        $ytdTarget     = $parseNumber($row['YTD_sd_Mar_2025'] ?? 0);
        $ytdActual     = $parseNumber($row['YTD_bayar_Mar_2025'] ?? 0);
        $less30days = $parseNumber($row['dari_1_sampai_30_DP'] ?? 0);
        $more31days = $parseNumber($row['dari_31_sampai_60_DP'] ?? 0);
        $more61days = $parseNumber($row['dari_61_sampai_90_DP'] ?? 0);
        $more90days = $parseNumber($row['diatas_90_DP'] ?? 0);
        $lebihbayar = abs($parseNumber($row['lebih_bayar'] ?? 0));
        $harganetto = $parseNumber($row['harga_netto'] ?? 0);
        $paybeforejan2025 = $parseNumber($row['Payment_Before_Jan_2025'] ?? 0);
        $ytdbayarmar2025 = $parseNumber($row['YTD_bayar_Mar_2025'] ?? 0);
        $collectioncash = $parseNumber($row['collection_target_cash_v'] ?? 0);
        $collectioninhouse = $parseNumber($row['collection_target_inhouse_v'] ?? 0);
        $collectionkpr = $parseNumber($row['collection_target_kpr_v'] ?? 0);


        if (!isset($summary[$type])) {
            $summary[$type] = [
                'jan_target' => 0,
                'jan_actual' => 0,
                'feb_target' => 0,
                'feb_actual' => 0,
                'mar_target' => 0,
                'mar_actual' => 0,
                'ytd_target'     => 0,
                'ytd_actual'     => 0,
                'less30days'     => 0,
                'more31days'     => 0,
                'more61days'     => 0,
                'more90days'     => 0,
                'lebihbayar'     => 0,
                'harganetto'     => 0,
                'paybeforejan2025' => 0,
                'ytdbayarmar2025' => 0,
                'collectioncash' => 0,
                'collectioninhouse' => 0,
                'collectionkpr' => 0,
                'total_meeting_target' => 0,
                'monthly_meeting_target' => 0,
            ];
        }

        $summary[$type]['jan_target'] += $jantarget;
        $summary[$type]['jan_actual'] += $janactual;
        $summary[$type]['feb_target'] += $febtarget;
        $summary[$type]['feb_actual'] += $febactual;
        $summary[$type]['mar_target']     += $martarget;
        $summary[$type]['mar_actual']     += $maractual;
        $summary[$type]['ytd_target']     += $ytdTarget;
        $summary[$type]['ytd_actual']     += $ytdActual;
        $summary[$type]['less30days']     += $less30days;
        $summary[$type]['more31days']     += $more31days;
        $summary[$type]['more61days']     += $more61days;
        $summary[$type]['more90days']     += $more90days;
        $summary[$type]['lebihbayar']     += $lebihbayar;
        $summary[$type]['harganetto']     += $harganetto;
        $summary[$type]['paybeforejan2025'] += $paybeforejan2025;
        $summary[$type]['ytdbayarmar2025'] += $ytdbayarmar2025;
        $summary[$type]['collectioncash'] += $collectioncash;
        $summary[$type]['collectioninhouse'] += $collectioninhouse;
        $summary[$type]['collectionkpr'] += $collectionkpr;
        $summary['TOTAL'] = $totals;

        
        // $summary[$type]['total_meeting_target'] = 0; // will be set later
        // $summary[$type]['monthly_meeting_target'] = 0; // will be

        // $totals['jan_target'] += $jantarget;
        // $totals['jan_actual'] += $janactual;
        // $totals['feb_target'] += $febtarget;
        // $totals['feb_actual'] += $febactual;
        $totals['mar_target']     += $martarget;
        $totals['mar_actual']     += $maractual;
        $totals['ytd_target']     += $ytdTarget;
        $totals['ytd_actual']     += $ytdActual;
        $totals['less30days']     += $less30days;
        $totals['more31days']     += $more31days;
        $totals['more61days']     += $more61days;
        $totals['more90days']     += $more90days;
        $totals['lebihbayar']     += $lebihbayar;
        $totals['harganetto']     += $harganetto;
        $totals['paybeforejan2025'] += $paybeforejan2025;
        $totals['ytdbayarmar2025'] += $ytdbayarmar2025;
        $totals['collectioncash'] += $collectioncash;
        $totals['collectioninhouse'] += $collectioninhouse;
        $totals['collectionkpr'] += $collectionkpr;
        $totals['total_meeting_target'] = $summary['TOTAL']['monthly_meeting_target'];


    }

    $summary['TOTAL'] = $totals;
    $total = $summary['TOTAL'];
    unset($summary['TOTAL']);
    ksort($summary);
    $summary['TOTAL'] = $total;
    $agingtotal = $total['less30days'] + $total['more31days'] + $total['more61days'] + $total['more90days'];

    // === OUTSTANDING A/R (ESCROW + AGING) ===
    $outstanding = [];
    $outstandingTotals = [
        'jatuh_tempo' => 0,
        'belum_jatuh_tempo' => 0,
        'total' => 0,
    ];

    // Build escrow
    $escrowTotals = [];
    if (is_array($rows3) && count($rows3) > 0) {
        foreach ($rows3 as $r) {
            $totalVal  = $parseNumber($r['Total'] ?? $r['total'] ?? 0);
            $hutangVal = $parseNumber($r['Hutang_Yang_Belum_Jatuh_Tempo'] ?? 0);

            $escrowTotals[] = [
                'total'  => $totalVal,   // Sudah Jatuh Tempo
                'hutang' => $hutangVal,  // Belum Jatuh Tempo
            ];

            $jatuh = $totalVal;
            $belum = $hutangVal;
            $sum   = $jatuh + $belum;

            $outstanding['ESCROW']['jatuh_tempo'] = ($outstanding['ESCROW']['jatuh_tempo'] ?? 0) + $jatuh;
            $outstanding['ESCROW']['belum_jatuh_tempo'] = ($outstanding['ESCROW']['belum_jatuh_tempo'] ?? 0) + $belum;
            $outstanding['ESCROW']['total'] = ($outstanding['ESCROW']['total'] ?? 0) + $sum;

            $outstandingTotals['jatuh_tempo'] += $jatuh;
            $outstandingTotals['belum_jatuh_tempo'] += $belum;
            $outstandingTotals['total'] += $sum;
        }
    }

    // === AGING ===
    if (isset($summary['TOTAL'])) {
        $agingTotal = 
            ($summary['TOTAL']['less30days'] ?? 0) +
            ($summary['TOTAL']['more31days'] ?? 0) +
            ($summary['TOTAL']['more61days'] ?? 0) +
            ($summary['TOTAL']['more90days'] ?? 0);
            // ($summary['TOTAL']['lebihbayar'] ?? 0);

        $belumAging = 
            ($summary['TOTAL']['harganetto'] ?? 0) -
            ($summary['TOTAL']['paybeforejan2025'] ?? 0) -
            ($summary['TOTAL']['ytdbayarmar2025'] ?? 0)-
            $agingTotal;

        $outstanding['AGING']['jatuh_tempo'] = $agingTotal;
        $outstanding['AGING']['belum_jatuh_tempo'] = $belumAging;
        $outstanding['AGING']['total'] = $agingTotal + $belumAging;

        $outstandingTotals['jatuh_tempo'] += $agingTotal;
        $outstandingTotals['belum_jatuh_tempo'] += $belumAging;
        $outstandingTotals['total'] += ($agingTotal + $belumAging);
    }

    // === TOTAL row ===
    $outstanding['TOTAL'] = $outstandingTotals;

    // Ensure escrowTotals is not empty for Blade
    if (empty($escrowTotals)) {
        $escrowTotals = [['total' => 0, 'hutang' => 0]];
    }


    // rows4 contains targetsales data
    $collectionTargets = [];

    foreach ($rows4 as $row) {
        $month = (int)($row['bulan'] ?? 0);

        if ($month > 0) {
            $collectionTargets[$month] = [
                'cash'    => (float)($row['collection_target_cash_v'] ?? 0),
                'inhouse' => (float)($row['collection_target_inhouse_v'] ?? 0),
                'kpr'     => (float)($row['collection_target_kpr_v'] ?? 0),
            ];
        }
    }

    // After calculating $summary and $collectionTargets
        foreach ($summary as $type => &$data) {
        $data['monthly_meeting_target'] = 0;
    }
    unset($data);

    foreach ($summary as $type => &$data) {
        if ($type === 'CASH') {
            $data['monthly_meeting_target'] = $collectionTargets[$currentMonth]['cash'] ?? 0;
        } elseif ($type === 'INHOUSE') {
            $data['monthly_meeting_target'] = $collectionTargets[$currentMonth]['inhouse'] ?? 0;
        } elseif ($type === 'KPR') {
            $data['monthly_meeting_target'] = $collectionTargets[$currentMonth]['kpr'] ?? 0;
        }
    }
    $summary['TOTAL']['monthly_meeting_target'] = 
        ($collectionTargets[$currentMonth]['cash'] ?? 0) +
        ($collectionTargets[$currentMonth]['inhouse'] ?? 0) +
        ($collectionTargets[$currentMonth]['kpr'] ?? 0);


    // which month to display? use request param or fallback to current month
    $currentMonth = $request->input('month', now()->month);

    return view('management-report', [
        'summary'           => $summary,
        'rows'              => $rows,
        'outstanding'       => $outstanding,
        'escrowTotals'      => $escrowTotals,
        'collectionTargets' => $collectionTargets,
        'currentMonth'      => $currentMonth,
    ]);

    }

}
