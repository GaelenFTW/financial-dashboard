<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use App\Models\PurchasePayment;
use Carbon\Carbon;

class ManagementReportController extends Controller
{
    protected $jwtController;

    public function __construct(JWTController $jwtController)
    {
        $this->jwtController = $jwtController;
    }

    public function index(Request $request)
    {

        $currentMonth = (int)$request->input('month', now()->month);
        $currentYear = (int)$request->input('year', now()->year);
        $projectId = $request->input('project_id');

        $query = PurchasePayment::where('data_year', $currentYear);
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        $payments = $query->get();
        $rows = $payments->map(fn($p) => $p->toArray())->toArray();

        $rows3 = $this->jwtController->fetchData('api3', ['escrow.php', 'login.php']);
        $rows4 = $this->jwtController->fetchData('api4', ['target.php', 'login.php']);

        if (empty($rows)) {
            return view('management-report', ['error' => 'No data found. Please upload Excel file first.']);
        }

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

        $monthShortUc = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];
        $monthShortLc = array_map('strtolower', $monthShortUc);

        $summary = [];
        $totals = [
            'ytd_target' => 0, 'ytd_actual' => 0,
            'less30days' => 0, 'more31days' => 0, 'more61days' => 0, 'more90days' => 0,
            'lebihbayar' => 0, 'harganetto' => 0,
            'paybeforejan' => 0, 'ytdbayar' => 0,
            'monthly_meeting_target' => 0,
        ];
        foreach ($monthShortLc as $m) {
            $totals["{$m}_target"] = 0;
            $totals["{$m}_actual"] = 0;
        }

        foreach ($rows as $row) {
            $rawType = $row['TypePembelian'] ?? '';
            $type = strtoupper(trim($rawType));
            if ($type === '' || $type === 'UNKNOWN') continue;

            if (!isset($summary[$type])) {
                $summary[$type] = $totals;
            }

            // monthly targets/actuals
            foreach ($monthShortUc as $num => $shortUc) {
                $lc = $monthShortLc[$num];
                $pField = "{$shortUc}_Year_Piutang";
                $aField = "{$shortUc}_Year_Payment";

                $tVal = $parseNumber($row[$pField] ?? 0);
                $aVal = $parseNumber($row[$aField] ?? 0);

                $summary[$type]["{$lc}_target"] += $tVal;
                $summary[$type]["{$lc}_actual"] += $aVal;

                $totals["{$lc}_target"] += $tVal;
                $totals["{$lc}_actual"] += $aVal;
            }

            // YTD accumulation per type (fixed)
            if (!isset($summary[$type]['ytd_target'])) {
                $summary[$type]['ytd_target'] = 0;
                $summary[$type]['ytd_actual'] = 0;
            }
            $ytdTarget = 0;
            $ytdActual = 0;
            for ($m = 1; $m <= $currentMonth; $m++) {
                $lc = $monthShortLc[$m];
                $pField = "{$monthShortUc[$m]}_Year_Piutang";
                $aField = "{$monthShortUc[$m]}_Year_Payment";

                $ytdTarget += $parseNumber($row[$pField] ?? 0);
                $ytdActual += $parseNumber($row[$aField] ?? 0);
            }
            $summary[$type]['ytd_target'] += $ytdTarget;
            $summary[$type]['ytd_actual'] += $ytdActual;

            // other accumulations
            $summary[$type]['less30days'] += $parseNumber($row['dari_1_sampai_30_DP'] ?? 0);
            $summary[$type]['more31days'] += $parseNumber($row['dari_31_sampai_60_DP'] ?? 0);
            $summary[$type]['more61days'] += $parseNumber($row['dari_61_sampai_90_DP'] ?? 0);
            $summary[$type]['more90days'] += $parseNumber($row['diatas_90_DP'] ?? 0);
            $summary[$type]['lebihbayar'] += abs($parseNumber($row['lebih_bayar'] ?? 0));
            $summary[$type]['harganetto'] += $parseNumber($row['harga_netto'] ?? 0);
            $summary[$type]['paybeforejan'] += $parseNumber($row['Payment_Before_01_Year'] ?? 0);
            $summary[$type]['ytdbayar'] += $ytdActual;

            // totals accumulation
            $totals['ytd_target'] += $ytdTarget;
            $totals['ytd_actual'] += $ytdActual;
            $totals['less30days'] += $parseNumber($row['dari_1_sampai_30_DP'] ?? 0);
            $totals['more31days'] += $parseNumber($row['dari_31_sampai_60_DP'] ?? 0);
            $totals['more61days'] += $parseNumber($row['dari_61_sampai_90_DP'] ?? 0);
            $totals['more90days'] += $parseNumber($row['diatas_90_DP'] ?? 0);
            $totals['lebihbayar'] += abs($parseNumber($row['lebih_bayar'] ?? 0));
            $totals['harganetto'] += $parseNumber($row['harga_netto'] ?? 0);
            $totals['paybeforejan'] += $parseNumber($row['Payment_Before_01_Year'] ?? 0);
            $totals['ytdbayar'] += $ytdActual;
        }

        // ESCROW & OUTSTANDING building (from API)
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
            ($summary['TOTAL']['paybeforejan'] ?? 0) -
            ($summary['TOTAL']['ytdbayar'] ?? 0) -
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

        $outstanding['TOTAL'] = $outstandingTotalsCalc;

        // collectionTargets from rows4 (from API)
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

        // inject meeting targets into summary per-type
      // filter targets by current project
        $filteredTargets = [];
        if (!empty($projectId) && is_array($rows4)) {
            foreach ($rows4 as $r) {
                if ((int)($r['project_id'] ?? 0) === (int)$projectId) {
                    $month = (int)($r['bulan'] ?? 0);
                    if ($month >= 1 && $month <= 12) {
                        $filteredTargets[$month] = [
                            'cash' => $parseNumber($r['collection_target_cash_v'] ?? 0),
                            'inhouse' => $parseNumber($r['collection_target_inhouse_v'] ?? 0),
                            'kpr' => $parseNumber($r['collection_target_kpr_v'] ?? 0),
                        ];
                    }
                }
            }
        }

        foreach ($summary as $typeKey => &$data) {
            $cleanType = strtoupper(trim($typeKey));
            $data['monthly_meeting_target'] = 0;

            if (isset($filteredTargets[$currentMonth])) {
                switch ($cleanType) {
                    case 'CASH':
                        $data['monthly_meeting_target'] = $filteredTargets[$currentMonth]['cash'] ?? 0;
                        break;
                    case 'INHOUSE':
                        $data['monthly_meeting_target'] = $filteredTargets[$currentMonth]['inhouse'] ?? 0;
                        break;
                    case 'KPR':
                        $data['monthly_meeting_target'] = $filteredTargets[$currentMonth]['kpr'] ?? 0;
                        break;
                }
            }
        }
        unset($data);

        // TOTAL meeting target
        $summary['TOTAL']['monthly_meeting_target'] = ($filteredTargets[$currentMonth]['cash'] ?? 0)
            + ($filteredTargets[$currentMonth]['inhouse'] ?? 0)
            + ($filteredTargets[$currentMonth]['kpr'] ?? 0);

        // Build presentation arrays for Blade
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

        // monthlyPerformance rows
        $monthlyPerformance = [];
        $monthlyTotalsCalc = ['meeting_target' => 0, 'sales_target' => 0, 'actual' => 0];

        foreach ($types as $type) {
            $type = strtoupper(trim($type));

            // Skip TOTAL if it exists in types
            if ($type === 'TOTAL') continue;

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

            $monthlyTotalsCalc['meeting_target'] += $meetingTarget;
            $monthlyTotalsCalc['sales_target']   += $salesTarget;
            $monthlyTotalsCalc['actual']         += $actual;
        }

        // Compute TOTAL row percentage & status
        $monthlyTotalsCalc['percentage'] = $monthlyTotalsCalc['sales_target'] > 0
            ? round(($monthlyTotalsCalc['actual'] / $monthlyTotalsCalc['sales_target']) * 100, 1)
            : 0.0;

        $monthlyTotalsCalc['status'] = $monthlyTotalsCalc['percentage'] >= 100 ? 'ACHIEVED' 
            : ($monthlyTotalsCalc['percentage'] >= 80 ? 'ON TRACK' : 'BELOW TARGET');

        // Append TOTAL row to the performance array
        $monthlyPerformance[] = [
            'payment'        => 'TOTAL',
            'meeting_target' => $monthlyTotalsCalc['meeting_target'],
            'sales_target'   => $monthlyTotalsCalc['sales_target'],
            'actual'         => $monthlyTotalsCalc['actual'],
            'percentage'     => $monthlyTotalsCalc['percentage'],
            'status'         => $monthlyTotalsCalc['status'],
        ];


        // YTD Performance
        $ytdPerformance = [];
        $ytdTotalsCalc = ['meeting_target' => 0, 'sales_target' => 0, 'actual' => 0];

        foreach ($types as $type) {
            $type = strtoupper(trim($type));
            if ($type === 'TOTAL') continue;

            // YTD meeting target sums only months for this project_id
            $meetingTarget = 0;
            for ($m = 1; $m <= $currentMonth; $m++) {
                if (isset($filteredTargets[$m])) {
                    switch ($type) {
                        case 'CASH':
                            $meetingTarget += $filteredTargets[$m]['cash'] ?? 0;
                            break;
                        case 'INHOUSE':
                            $meetingTarget += $filteredTargets[$m]['inhouse'] ?? 0;
                            break;
                        case 'KPR':
                            $meetingTarget += $filteredTargets[$m]['kpr'] ?? 0;
                            break;
                    }
                }
            }

            $ytdSalesTarget = $summary[$type]['ytd_target'] ?? 0;
            $ytdActual      = $summary[$type]['ytd_actual'] ?? 0;
            $pct = $ytdSalesTarget > 0 ? round(($ytdActual / $ytdSalesTarget) * 100, 1) : 0.0;
            $status = $pct >= 100 ? 'ACHIEVED' : ($pct >= 80 ? 'ON TRACK' : 'BELOW TARGET');

            $ytdPerformance[] = [
                'payment'        => $type,
                'meeting_target' => $meetingTarget,
                'sales_target'   => $ytdSalesTarget,
                'actual'         => $ytdActual,
                'percentage'     => $pct,
                'status'         => $status,
            ];

            $ytdTotalsCalc['meeting_target'] += $meetingTarget;
            $ytdTotalsCalc['sales_target']   += $ytdSalesTarget;
            $ytdTotalsCalc['actual']         += $ytdActual;
        }

        // TOTAL row for YTD
        $ytdTotalsCalc['percentage'] = $ytdTotalsCalc['sales_target'] > 0
            ? round(($ytdTotalsCalc['actual'] / $ytdTotalsCalc['sales_target']) * 100, 1)
            : 0.0;
        $ytdTotalsCalc['status'] = $ytdTotalsCalc['percentage'] >= 100 ? 'ACHIEVED'
            : ($ytdTotalsCalc['percentage'] >= 80 ? 'ON TRACK' : 'BELOW TARGET');

        $ytdPerformance[] = [
            'payment'        => 'TOTAL',
            'meeting_target' => $ytdTotalsCalc['meeting_target'],
            'sales_target'   => $ytdTotalsCalc['sales_target'],
            'actual'         => $ytdTotalsCalc['actual'],
            'percentage'     => $ytdTotalsCalc['percentage'],
            'status'         => $ytdTotalsCalc['status'],
        ];

        // AGING rows
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

        // OUTSTANDING rows
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

        return view('management-report', [
            'monthlyPerformance' => $monthlyPerformance,
            'monthlyTotals' => $monthlyTotalsCalc,
            'ytdPerformance' => $ytdPerformance,
            'ytdTotals' => $ytdTotalsCalc,
            'aging' => $aging,
            'agingTotals' => $agingTotals,
            'outstanding' => $outstandingRows,
            'outstandingTotals' => $outstandingTotals,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,

        ]);
    }

    public function export(Request $request){
        $month = (int)$request->input('month', now()->month);
        $year = (int)$request->input('year', now()->year);
        $projectId = $request->input('project_id');

        // Reuse your index logic to get data
        $view = $this->index($request);
        $data = $view->getData();

        $spreadsheet = new Spreadsheet();

        // === 1. MONTHLY PERFORMANCE ===
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Monthly Performance');
        $sheet->fromArray([
            ['PAYMENT SYSTEM', 'MEETING TARGET', 'SALES TARGET', 'ACTUAL', '% (Meeting)', '% (Sales)', 'STATUS']
        ], null, 'A1');

        $row = 2;
        foreach ($data['monthlyPerformance'] ?? [] as $item) {
            $sheet->setCellValue("A{$row}", $item['payment']);
            $sheet->setCellValue("B{$row}", $item['meeting_target']);
            $sheet->setCellValue("C{$row}", $item['sales_target']);
            $sheet->setCellValue("D{$row}", $item['actual']);
            $sheet->setCellValue("E{$row}", $item['meeting_target'] > 0 ? round($item['actual'] / $item['meeting_target'] * 100, 1).'%' : '0%');
            $sheet->setCellValue("F{$row}", $item['percentage'].'%');
            $sheet->setCellValue("G{$row}", $item['status']);
            $row++;
        }

        // === 2. YTD PERFORMANCE ===
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('YTD Performance');
        $sheet2->fromArray([
            ['PAYMENT SYSTEM', 'MEETING TARGET', 'SALES TARGET', 'ACTUAL', '% (Meeting)', '% (Sales)', 'STATUS']
        ], null, 'A1');

        $row = 2;
        foreach ($data['ytdPerformance'] ?? [] as $item) {
            $sheet2->setCellValue("A{$row}", $item['payment']);
            $sheet2->setCellValue("B{$row}", $item['meeting_target']);
            $sheet2->setCellValue("C{$row}", $item['sales_target']);
            $sheet2->setCellValue("D{$row}", $item['actual']);
            $sheet2->setCellValue("E{$row}", $item['meeting_target'] > 0 ? round($item['actual'] / $item['meeting_target'] * 100, 1).'%' : '0%');
            $sheet2->setCellValue("F{$row}", $item['percentage'].'%');
            $sheet2->setCellValue("G{$row}", $item['status']);
            $row++;
        }

        // === 3. AGING ===
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Aging');
        $sheet3->fromArray([
            ['PAYMENT SYSTEM', '<30 DAYS', '31–60 DAYS', '61–90 DAYS', '>90 DAYS', 'LEBIH BAYAR']
        ], null, 'A1');

        $row = 2;
        foreach ($data['aging'] ?? [] as $item) {
            $sheet3->setCellValue("A{$row}", $item['payment']);
            $sheet3->setCellValue("B{$row}", $item['lt30']);
            $sheet3->setCellValue("C{$row}", $item['d30_60']);
            $sheet3->setCellValue("D{$row}", $item['d60_90']);
            $sheet3->setCellValue("E{$row}", $item['gt90']);
            $sheet3->setCellValue("F{$row}", $item['lebih_bayar']);
            $row++;
        }

        // === 4. OUTSTANDING ===
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Outstanding');
        $sheet4->fromArray([
            ['TYPE', 'JATUH TEMPO', 'BELUM JATUH TEMPO', 'TOTAL', '%']
        ], null, 'A1');

        $row = 2;
        foreach ($data['outstanding'] ?? [] as $item) {
            $sheet4->setCellValue("A{$row}", $item['type']);
            $sheet4->setCellValue("B{$row}", $item['jatuh_tempo']);
            $sheet4->setCellValue("C{$row}", $item['belum_jatuh_tempo']);
            $sheet4->setCellValue("D{$row}", $item['total']);
            $sheet4->setCellValue("E{$row}", $item['percentage'].'%');
            $row++;
        }

        // === Output file ===
        $filename = "Management_Report_{$year}_{$month}.xlsx";
        $writer = new Xlsx($spreadsheet);
        $tempFile = storage_path("app/public/{$filename}");
        $writer->save($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }
}