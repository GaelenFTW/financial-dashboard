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

    public function index(Request $request)
    {
        $rows = $this->getData();

        if (isset($rows['error'])) {
            return view('management-report', ['error' => $rows['error']]);
        }

        // Group by TypePembelian and sum both Monthly and YTD values
        $summary = [];
        $totals = [
            'monthly_target' => 0,
            'monthly_actual' => 0,
            'ytd_target' => 0,
            'ytd_actual' => 0,
        ];
        
        foreach ($rows as $row) {
            $type = $row['TypePembelian'] ?? null;
            
            // Skip rows without TypePembelian or with UNKNOWN/empty values
            if (empty($type) || $type === 'UNKNOWN') {
                continue;
            }
            
            // Handle Monthly Mar 2025 Piutang (Target)
            $monthlyTargetRaw = $row['Mar_2025_Piutang'] ?? '';
            $monthlyTarget = 0;
            if (!empty($monthlyTargetRaw)) {
                $cleanValue = str_replace(',', '', trim($monthlyTargetRaw));
                $monthlyTarget = is_numeric($cleanValue) ? (float) $cleanValue : 0;
            }
            
            // Handle Monthly Mar 2025 Payment (Actual)
            $monthlyActualRaw = $row['Mar_2025_Payment'] ?? '';
            $monthlyActual = 0;
            if (!empty($monthlyActualRaw)) {
                $cleanValue = str_replace(',', '', trim($monthlyActualRaw));
                $monthlyActual = is_numeric($cleanValue) ? (float) $cleanValue : 0;
            }
            
            // Handle YTD Target
            $ytdTargetRaw = $row['YTD_sd_Mar_2025'] ?? '';
            $ytdTarget = 0;
            if (!empty($ytdTargetRaw)) {
                $cleanValue = str_replace(',', '', trim($ytdTargetRaw));
                $ytdTarget = is_numeric($cleanValue) ? (float) $cleanValue : 0;
            }
            
            // Handle YTD Actual
            $ytdActualRaw = $row['YTD_bayar_Mar_2025'] ?? '';
            $ytdActual = 0;
            if (!empty($ytdActualRaw)) {
                $cleanValue = str_replace(',', '', trim($ytdActualRaw));
                $ytdActual = is_numeric($cleanValue) ? (float) $cleanValue : 0;
            }
            
            if (!isset($summary[$type])) {
                $summary[$type] = [
                    'monthly_target' => 0,
                    'monthly_actual' => 0,
                    'ytd_target' => 0,
                    'ytd_actual' => 0,
                ];
            }
            
            $summary[$type]['monthly_target'] += $monthlyTarget;
            $summary[$type]['monthly_actual'] += $monthlyActual;
            $summary[$type]['ytd_target'] += $ytdTarget;
            $summary[$type]['ytd_actual'] += $ytdActual;
            
            $totals['monthly_target'] += $monthlyTarget;
            $totals['monthly_actual'] += $monthlyActual;
            $totals['ytd_target'] += $ytdTarget;
            $totals['ytd_actual'] += $ytdActual;
        }

        // Add total row
        $summary['TOTAL'] = $totals;

        // Sort by TypePembelian, but keep TOTAL at the end
        $total = $summary['TOTAL'];
        unset($summary['TOTAL']);
        ksort($summary);
        $summary['TOTAL'] = $total;

        // Debug: Check what we're actually sending
        // Uncomment the line below to debug the data structure
        // dd($summary);
        
        return view('management-report', [
            'summary' => $summary,
            'rows' => $rows
        ]);
    }
}