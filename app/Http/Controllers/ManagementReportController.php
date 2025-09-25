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

    if (isset($rows['error'])) {
        return view('management-report', ['error' => $rows['error']]);
    }

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
        'monthly_target' => 0,
        'monthly_actual' => 0,
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
    ];

    foreach ($rows as $row) {
        $type = $row['TypePembelian'] ?? null;
        if (empty($type) || $type === 'UNKNOWN') {
            continue;
        }

        $monthlyTarget = $parseNumber($row['Mar_2025_Piutang'] ?? 0);
        $monthlyActual = $parseNumber($row['Mar_2025_Payment'] ?? 0);
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
                'monthly_target' => 0,
                'monthly_actual' => 0,
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
            ];
        }

        $summary[$type]['monthly_target'] += $monthlyTarget;
        $summary[$type]['monthly_actual'] += $monthlyActual;
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

        $totals['monthly_target'] += $monthlyTarget;
        $totals['monthly_actual'] += $monthlyActual;
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


    return view('management-report', [
        'summary' => $summary,
        'rows' => $rows,
        'outstanding' => $outstanding,
        'escrowTotals' => $escrowTotals,
    ]);
}

}
