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

        // Filter only relevant columns
        $filtered = array_map(fn($r) => [
            'TypePembelian'     => $r['TypePembelian'] ?? 'UNKNOWN',
            'Mar_2025_Piutang'  => (float) str_replace(',', '', $r['Mar_2025_Piutang'] ?? 0),
            'Mar_2025_Payment'  => (float) str_replace(',', '', $r['Mar_2025_Payment'] ?? 0),
        ], $rows);

        // Group by TypePembelian and sum values
        $summary = [];
        foreach ($filtered as $row) {
            $type = $row['TypePembelian'] ?: 'UNKNOWN';
            if (!isset($summary[$type])) {
                $summary[$type] = [
                    'Target' => 0,
                    'Actual' => 0,
                ];
            }
            $summary[$type]['Target'] += $row['Mar_2025_Piutang'];
            $summary[$type]['Actual'] += $row['Mar_2025_Payment'];
        }

        // Calculate total
        $summary['TOTAL'] = [
            'Target' => array_sum(array_column($summary, 'Target')),
            'Actual' => array_sum(array_column($summary, 'Actual')),
        ];

        return view('management-report', [
            'summary' => $summary,
        ]);
    }

}
