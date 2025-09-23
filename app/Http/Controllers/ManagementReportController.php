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

        // Filter only the columns you need
        $filtered = array_map(function ($r) {
            return [
                'CustomerName'       => $r['CustomerName'] ?? '',
                'Cluster'            => $r['Cluster'] ?? '',
                'TypePembelian'      => $r['TypePembelian'] ?? '',
                'Mar_2025_Piutang'   => $r['Mar_2025_Piutang'] ?? 0,
                'Mar_2025_Payment'   => $r['Mar_2025_Payment'] ?? 0,
            ];
        }, $rows);

        return view('management-report', [
            'rows' => $filtered
        ]);
    }
}
