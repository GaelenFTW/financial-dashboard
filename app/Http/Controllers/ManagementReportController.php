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

        // === SALES SUMMARY (Monthly + YTD) ===
        $summary = [];
        $totals = [
            'monthly_target' => 0,
            'monthly_actual' => 0,
            'ytd_target'     => 0,
            'ytd_actual'     => 0,
        ];

        foreach ($rows as $row) {
            $type = $row['TypePembelian'] ?? null;
            if (empty($type) || $type === 'UNKNOWN') {
                continue;
            }

            $monthlyTarget = (float) str_replace(',', '', $row['Mar_2025_Piutang'] ?? 0);
            $monthlyActual = (float) str_replace(',', '', $row['Mar_2025_Payment'] ?? 0);
            $ytdTarget     = (float) str_replace(',', '', $row['YTD_sd_Mar_2025'] ?? 0);
            $ytdActual     = (float) str_replace(',', '', $row['YTD_bayar_Mar_2025'] ?? 0);

            if (!isset($summary[$type])) {
                $summary[$type] = [
                    'monthly_target' => 0,
                    'monthly_actual' => 0,
                    'ytd_target'     => 0,
                    'ytd_actual'     => 0,
                ];
            }

            $summary[$type]['monthly_target'] += $monthlyTarget;
            $summary[$type]['monthly_actual'] += $monthlyActual;
            $summary[$type]['ytd_target']     += $ytdTarget;
            $summary[$type]['ytd_actual']     += $ytdActual;

            $totals['monthly_target'] += $monthlyTarget;
            $totals['monthly_actual'] += $monthlyActual;
            $totals['ytd_target']     += $ytdTarget;
            $totals['ytd_actual']     += $ytdActual;
        }

        $summary['TOTAL'] = $totals;
        $total = $summary['TOTAL'];
        unset($summary['TOTAL']);
        ksort($summary);
        $summary['TOTAL'] = $total;

        // === OUTSTANDING A/R (AGING + ESCROW) ===
        $outstanding = [];
        $outstandingTotals = [
            'jatuh_tempo'       => 0,
            'belum_jatuh_tempo' => 0,
            'total'             => 0,
        ];

        foreach ($rows2 as $row) {
        // Force type = ESCROW since your data doesn’t have a "Type" field
        $type = 'ESCROW';

        $total = (float) str_replace(',', '', $row['Total'] ?? 0);
        $belum = (float) str_replace(',', '', $row['Hutang_Yang_Belum_Jatuh_Tempo'] ?? 0);
        $jatuh = $total - $belum;

        if (!isset($outstanding[$type])) {
            $outstanding[$type] = [
                'jatuh_tempo'       => 0,
                'belum_jatuh_tempo' => 0,
                'total'             => 0,
            ];
        }

        $outstanding[$type]['jatuh_tempo']       += $jatuh;
        $outstanding[$type]['belum_jatuh_tempo'] += $belum;
        $outstanding[$type]['total']             += $total;

        $outstandingTotals['jatuh_tempo']       += $jatuh;
        $outstandingTotals['belum_jatuh_tempo'] += $belum;
        $outstandingTotals['total']             += $total;
    }


        $outstanding['TOTAL'] = $outstandingTotals;

        return view('management-report', [
            'summary'     => $summary,
            'rows'        => $rows,
            'outstanding' => $outstanding,
        ]);
    }
}
