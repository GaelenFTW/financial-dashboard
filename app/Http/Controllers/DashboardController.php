<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $jwtController;

    public function __construct(JWTController $jwtController)
    {
        $this->jwtController = $jwtController;
    }


    public function index(Request $request)
    {
        $rows  = $this->jwtController->fetchData1('api1', ['index.php', 'login.php']);

        if (isset($rows['error'])) {
            return view('dashboard', ['error' => $rows['error']]);
        }

        $rows = array_values(array_filter($rows, fn($r) => is_array($r)));

        // adminid filter
        $user = auth()->user();
        $userAdminId = $this->resolveUserAdminId($user);
        if ($user && $userAdminId !== 999) {
            $rows = array_values(array_filter($rows, function ($row) use ($userAdminId) {
                $rowAdminId = $row['AdminID'] ?? $row['adminid'] ?? $row['AdminId'] ?? $row['admin_id'] ?? 0;
                return (int) $rowAdminId === (int) $userAdminId;
            }));
        }

        $rows = $this->applyFilters($rows, $request);

        $totalRevenue = 0;
       $rows = array_map(function ($row) {
        if (isset($row['HrgJualTotal'])) {
            // Remove commas, spaces, etc. then cast to float
            $row['HrgJualTotal'] = (float) str_replace([',', ' '], '', $row['HrgJualTotal']);
        }
        return $row;
    }, $rows);

    // then safe to sum
        $totalRevenue = array_sum(array_column($rows, 'HrgJualTotal'));

        $numCustomers = count(array_unique(array_map(fn($r) => $r['CustomerName'] ?? '', $rows)));
        $productsSold = count($rows);
        $avgRevenue = $productsSold > 0 ? ($totalRevenue / $productsSold) : 0;

        // Top 10 customers
        $customerRevenue = [];
        foreach ($rows as $row) {
            $name = $row['CustomerName'] ?? 'Unknown';
            $customerRevenue[$name] = ($customerRevenue[$name] ?? 0) + $this->toFloat($row['HrgJualTotal'] ?? 0);
        }
        $customers = collect($customerRevenue)->sortDesc()->take(10);

        // Top 10 products (type_unit)
        $productRevenue = [];
        foreach ($rows as $row) {
            $product = $row['type_unit'] ?? 'Unknown';
            $productRevenue[$product] = ($productRevenue[$product] ?? 0) + $this->toFloat($row['HrgJualTotal'] ?? 0);
        }
        $products = collect($productRevenue)->sortDesc()->take(10);

        return view('dashboard', [
            'customers'    => $customers,
            'products'     => $products,
            'totalRevenue' => $totalRevenue,
            'numCustomers' => $numCustomers,
            'productsSold' => $productsSold,
            'avgRevenue'   => $avgRevenue,
            'filters'      => $request->all(),
        ]);
    }

//export 10 customers
    public function exportTopCustomers(Request $request)
    {
        $rows = $this->jwtController->fetchData1();
        $rows = $this->applyFilters($rows, $request);
        $customerRevenue = [];
        foreach ($rows as $row) {
            $name = $row['CustomerName'] ?? 'Unknown';
            $customerRevenue[$name] = ($customerRevenue[$name] ?? 0) + $this->toFloat($row['HrgJualTotal'] ?? 0);
        }
        $customers = collect($customerRevenue)->sortDesc()->take(10);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Customer');
        $sheet->setCellValue('B1', 'Revenue');

        $rowIndex = 2;
        foreach ($customers as $name => $revenue) {
            $sheet->setCellValue("A{$rowIndex}", $name);
            $sheet->setCellValue("B{$rowIndex}", $revenue);
            $rowIndex++;
        }

        $fileName = 'top_customers.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'cust_export_');
        $writer->save($tmp);

        return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
    }

//export 10 products
    public function exportTopProducts(Request $request)
    {
        $rows = $this->jwtController->fetchData1();

        $productRevenue = [];
        foreach ($rows as $row) {
            $product = $row['type_unit'] ?? 'Unknown';
            $productRevenue[$product] = ($productRevenue[$product] ?? 0) + $this->toFloat($row['HrgJualTotal'] ?? 0);
        }
        $products = collect($productRevenue)->sortDesc()->take(10);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Product');
        $sheet->setCellValue('B1', 'Revenue');

        $rowIndex = 2;
        foreach ($products as $product => $revenue) {
            $sheet->setCellValue("A{$rowIndex}", $product);
            $sheet->setCellValue("B{$rowIndex}", $revenue);
            $rowIndex++;
        }

        $fileName = 'top_products.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'prod_export_');
        $writer->save($tmp);

        return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
    }

    //export filtered
    public function exportFilteredData(Request $request)
    {
        $rows = $this->jwtController->fetchData1();
        $rows = array_values(array_filter($rows, fn($r) => is_array($r)));

        // Admin filter same as index
        $user = auth()->user();
        $userAdminId = $this->resolveUserAdminId($user);
        if ($user && $userAdminId !== 0) {
            $rows = array_values(array_filter($rows, function ($row) use ($userAdminId) {
                $rowAdminId = $row['AdminID'] ?? $row['adminid'] ?? $row['AdminId'] ?? $row['admin_id'] ?? 0;
                return (int) $rowAdminId === (int) $userAdminId;
            }));
        }

        $rows = $this->applyFilters($rows, $request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Columns (same order you requested)
        $columns = [
            "No", "purchaseletter_id", "Cluster", "Block", "Unit", "CustomerName", "PurchaseDate", "LunasDate",
            "is_ppndtp", "persen_ppndtp", "harga_netto", "TotalPPN", "harga_bbnsertifikat", "harga_bajb",
            "harga_bphtb", "harga_administrasi", "harga_paket_tambahan", "harga_admsubsidi", "biaya_asuransi",
            "HrgJualTotal", "disc_collection", "HrgJualTotalminDiscColl", "TypePembelian", "bank_induk", "KPP",
            "JenisKPR", "Salesman", "Member", "tanggal_akad", "persen_progress_bangun", "type_unit",
            "Amount_Before_Jan_2024", "Piutang_Before_Jan_2024", "Payment_Before_Jan_2024",
            "Jan_2024_DueDate", "Jan_2024_Type", "Jan_2024_Piutang", "Jan_2024_CairDate", "Jan_2024_Payment",
            "Feb_2024_DueDate", "Feb_2024_Type", "Feb_2024_Piutang", "Feb_2024_CairDate", "Feb_2024_Payment",
            "Mar_2024_DueDate", "Mar_2024_Type", "Mar_2024_Piutang", "Mar_2024_CairDate", "Mar_2024_Payment",
            "Apr_2024_DueDate", "Apr_2024_Type", "Apr_2024_Piutang", "Apr_2024_CairDate", "Apr_2024_Payment",
            "May_2024_DueDate", "May_2024_Type", "May_2024_Piutang", "May_2024_CairDate", "May_2024_Payment",
            "Jun_2024_DueDate", "Jun_2024_Type", "Jun_2024_Piutang", "Jun_2024_CairDate", "Jun_2024_Payment",
            "Jul_2024_DueDate", "Jul_2024_Type", "Jul_2024_Piutang", "Jul_2024_CairDate", "Jul_2024_Payment",
            "Aug_2024_DueDate", "Aug_2024_Type", "Aug_2024_Piutang", "Aug_2024_CairDate", "Aug_2024_Payment",
            "Sep_2024_DueDate", "Sep_2024_Type", "Sep_2024_Piutang", "Sep_2024_CairDate", "Sep_2024_Payment",
            "Oct_2024_DueDate", "Oct_2024_Type", "Oct_2024_Piutang", "Oct_2024_CairDate", "Oct_2024_Payment",
            "Piutang_After_Oct_2024", "Payment_After_Oct_2024", "YTD_sd_Oct_2024", "YTD_bayar_Oct_2024", "selisih",
            "dari_1_sampai_30_DP", "dari_31_sampai_60_DP", "dari_61_sampai_90_DP", "diatas_90_DP", "lebih_bayar",
            "AdminID"
        ];

        // header row
        foreach ($columns as $i => $heading) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $heading);
        }

        $rowIndex = 2;
        $counter = 1;
        foreach ($rows as $r) {
            foreach ($columns as $i => $colName) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                if ($colName === 'No') {
                    $sheet->setCellValue($col . $rowIndex, $counter);
                } else {
                    $value = $r[$colName] ?? $r[strtolower($colName)] ?? '';
                    $sheet->setCellValue($col . $rowIndex, $value);
                }
            }
            $rowIndex++;
            $counter++;
        }

        $fileName = 'dashboard_filtered.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'dash_export_');
        $writer->save($tmp);

        return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
    }

    //helpers

    protected function applyFilters(array $rows, Request $request): array
    {
        $cluster = $request->query('cluster');
        $typepembelian = $request->query('typepembelian');
        $customername = $request->query('customername');
        $type_unit = $request->query('type_unit');
        $start = $request->query('startdate');
        $end = $request->query('enddate');

        return array_values(array_filter($rows, function ($row) use ($cluster, $typepembelian, $customername, $type_unit, $start, $end) {
            if ($cluster && stripos($row['Cluster'] ?? '', $cluster) === false) return false;
            if ($typepembelian && stripos($row['TypePembelian'] ?? '', $typepembelian) === false) return false;
            if ($customername && stripos($row['CustomerName'] ?? '', $customername) === false) return false;
            if ($type_unit && stripos($row['type_unit'] ?? '', $type_unit) === false) return false;

            if ($start || $end) {
                $pd = $this->parseDate($row['PurchaseDate'] ?? null);
                if (!$pd) return false;
                if ($start) {
                    $from = Carbon::parse($start)->startOfDay();
                    if ($pd->lt($from)) return false;
                }
                if ($end) {
                    $to = Carbon::parse($end)->endOfDay();
                    if ($pd->gt($to)) return false;
                }
            }

            return true;
        }));
    }

    // Accept dd-mm-yyyy, d/m/Y, Y-m-d, etc.
    protected function parseDate($raw)
    {
        if (!$raw) return null;
        $raw = trim($raw);

        // Try common formats
        $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd-m-Y H:i:s', 'Y-m-d H:i:s'];
        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $raw);
                if ($dt) return $dt;
            } catch (\Exception $e) {
            }
        }

        $ts = strtotime(str_replace('-', '/', $raw));
        if ($ts !== false) {
            return Carbon::createFromTimestamp($ts);
        }
        return null;
    }

    protected function toFloat($val)
    {
        if ($val === null || $val === '') return 0.0;
        $s = (string) $val;
        $s = str_replace(['.', ' '], ['', ''], $s); // remove dots and spaces
        if (strpos($s, ',') !== false && substr_count($s, ',') === 1) {
            $s = str_replace(',', '.', $s);
        }
        $s = str_replace(',', '.', $s);
        return (float) $s;
    }

    protected function resolveUserAdminId($user)
    {
        if (!$user) return 0;
        return (int) ($user->AdminID ?? $user->adminid ?? $user->AdminId ?? $user->admin_id ?? 0);
    }
}
