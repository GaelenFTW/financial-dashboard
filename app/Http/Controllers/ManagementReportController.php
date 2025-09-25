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
        $rows2 = $this->jwtController->fetchData3();
        return is_array($rows2) ? $rows2 : [];
    }

    public function index(Request $request)
    {

    $rows  = $this->getData();
    $rows2 = $this->getData3();

    if (isset($rows['error'])) {
        return view('management-report', ['error' => $rows['error']]);
    }

    // ---------- helper to parse numbers robustly ----------
    $parseNumber = function($val) {
        if ($val === null || $val === '') return 0.0;
        $s = (string) $val;
        $s = trim($s);

        if (strpos($s, '.') !== false && strpos($s, ',') !== false) {
            $s = str_replace('.', '', $s);    
            $s = str_replace(',', '.', $s);   
        } else {
            // If only comma present -> treat comma as decimal separator
            if (strpos($s, ',') !== false && strpos($s, '.') === false) {
                $s = str_replace(',', '.', $s);
            }
        }

        // strip any non-digit/period/minus
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

        $totals['monthly_target'] += $monthlyTarget;
        $totals['monthly_actual'] += $monthlyActual;
        $totals['ytd_target']     += $ytdTarget;
        $totals['ytd_actual']     += $ytdActual;
        $totals['less30days']     += $less30days;
        $totals['more31days']     += $more31days;
        $totals['more61days']     += $more61days;
        $totals['more90days']     += $more90days;
        $totals['lebihbayar']     += $lebihbayar;
    }

    $summary['TOTAL'] = $totals;
    $total = $summary['TOTAL'];
    unset($summary['TOTAL']);
    ksort($summary);
    $summary['TOTAL'] = $total;

    // === OUTSTANDING A/R (ESCROW aggregation from rows2) ===
    $outstanding = [];
    $outstandingTotals = [
        'jatuh_tempo' => 0,
        'belum_jatuh_tempo' => 0,
        'total' => 0,
    ];

    // Build escrowTotals (array of rows with keys total & hutang) for Blade
    $escrowTotals = [];

    if (is_array($rows2) && count($rows2) > 0) {
        foreach ($rows2 as $r) {
            // Parse fields from the escrow JSON (case-insensitive fallback)
            $totalRaw = $r['Total'] ?? $r['total'] ?? 0;
            $hutangRaw = $r['Hutang_Yang_Belum_Jatuh_Tempo'] ?? $r['Hutang_Yang_Belum_Jatuh_Tempo'] ?? 0;

            $totalVal = $parseNumber($totalRaw);
            $hutangVal = $parseNumber($hutangRaw);

            // push into escrowTotals (Blade will iterate this)
            $escrowTotals[] = [
                'total' => $totalVal,   // Sudah Jatuh Tempo (per your mapping)
                'hutang' => $hutangVal, // Belum Jatuh Tempo
            ];

            // For the outstanding / TOTAL aggregation use ESCROW key
            $type = 'ESCROW';
            if (!isset($outstanding[$type])) {
                $outstanding[$type] = [
                    'jatuh_tempo' => 0,
                    'belum_jatuh_tempo' => 0,
                    'total' => 0,
                ];
            }

            $jatuh = $totalVal; 
            $belum = $hutangVal;
            $sum = $jatuh + $belum;

            $outstanding[$type]['jatuh_tempo'] += $jatuh;
            $outstanding[$type]['belum_jatuh_tempo'] += $belum;
            $outstanding[$type]['total'] += $sum;

            $outstandingTotals['jatuh_tempo'] += $jatuh;
            $outstandingTotals['belum_jatuh_tempo'] += $belum;
            $outstandingTotals['total'] += $sum;
        }
    }

    // Ensure AGING exists so UI shows both rows (optional)
    if (!isset($outstanding['AGING'])) {
        $outstanding['AGING'] = [
            'jatuh_tempo' => 0,
            'belum_jatuh_tempo' => 0,
            'total' => 0,
        ];
    }

    $outstanding['TOTAL'] = $outstandingTotals;

    // If no escrow rows provided, ensure escrowTotals at least has one row (prevents empty Blade)
    if (empty($escrowTotals)) {
        $escrowTotals = [
            ['total' => 0, 'hutang' => 0],
        ];
    }

    return view('management-report', [
        'summary' => $summary,
        'rows' => $rows,
        'outstanding' => $outstanding,
        'escrowTotals' => $escrowTotals,
    ]);
}

}
