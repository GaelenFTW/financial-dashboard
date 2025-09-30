<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Models\PurchasePayment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get data from PurchasePayment model
        $query = PurchasePayment::query();
        
        // Apply filters
        $query = $this->applyFilters($query, $request);
        
        $payments = $query->get();

        // Calculate metrics
        $totalRevenue = $payments->sum('HrgJualTotal');
        $numCustomers = $payments->pluck('CustomerName')->unique()->count();
        $productsSold = $payments->count();
        $avgRevenue = $productsSold > 0 ? ($totalRevenue / $productsSold) : 0;

        // Top 10 customers
        $customerRevenue = $payments->groupBy('CustomerName')->map(function ($group) {
            return $group->sum('HrgJualTotal');
        })->sortDesc()->take(10);

        // Top 10 products (type_unit)
        $productRevenue = $payments->groupBy('type_unit')->map(function ($group) {
            return $group->sum('HrgJualTotal');
        })->sortDesc()->take(10);

        return view('dashboard', [
            'customers'    => $customerRevenue,
            'products'     => $productRevenue,
            'totalRevenue' => $totalRevenue,
            'numCustomers' => $numCustomers,
            'productsSold' => $productsSold,
            'avgRevenue'   => $avgRevenue,
            'filters'      => $request->all(),
        ]);
    }

    public function exportTopCustomers(Request $request)
    {
        $query = PurchasePayment::query();
        $query = $this->applyFilters($query, $request);
        $payments = $query->get();

        $customerRevenue = $payments->groupBy('CustomerName')->map(function ($group) {
            return $group->sum('HrgJualTotal');
        })->sortDesc()->take(10);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Customer');
        $sheet->setCellValue('B1', 'Revenue');

        $rowIndex = 2;
        foreach ($customerRevenue as $name => $revenue) {
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

    public function exportTopProducts(Request $request)
    {
        $query = PurchasePayment::query();
        $query = $this->applyFilters($query, $request);
        $payments = $query->get();

        $productRevenue = $payments->groupBy('type_unit')->map(function ($group) {
            return $group->sum('HrgJualTotal');
        })->sortDesc()->take(10);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Product');
        $sheet->setCellValue('B1', 'Revenue');

        $rowIndex = 2;
        foreach ($productRevenue as $product => $revenue) {
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

    public function exportFilteredData(Request $request)
    {
        $query = PurchasePayment::query();
        $query = $this->applyFilters($query, $request);
        $payments = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Get all column names dynamically from the first record
        if ($payments->isEmpty()) {
            return redirect()->back()->with('error', 'No data to export');
        }

        $firstRecord = $payments->first();
        $columns = array_keys($firstRecord->getAttributes());

        // Set headers
        foreach ($columns as $i => $heading) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $heading);
        }

        // Fill data
        $rowIndex = 2;
        foreach ($payments as $payment) {
            foreach ($columns as $i => $colName) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $value = $payment->$colName ?? '';
                $sheet->setCellValue($col . $rowIndex, $value);
            }
            $rowIndex++;
        }

        $fileName = 'dashboard_filtered_' . date('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'dash_export_');
        $writer->save($tmp);

        return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
    }

    protected function applyFilters($query, Request $request)
    {
        if ($request->filled('cluster')) {
            $query->where('Cluster', 'like', '%' . $request->cluster . '%');
        }

        if ($request->filled('typepembelian')) {
            $query->where('TypePembelian', 'like', '%' . $request->typepembelian . '%');
        }

        if ($request->filled('customername')) {
            $query->where('CustomerName', 'like', '%' . $request->customername . '%');
        }

        if ($request->filled('type_unit')) {
            $query->where('type_unit', 'like', '%' . $request->type_unit . '%');
        }

        if ($request->filled('startdate')) {
            $query->whereDate('PurchaseDate', '>=', $request->startdate);
        }

        if ($request->filled('enddate')) {
            $query->whereDate('PurchaseDate', '<=', $request->enddate);
        }

        return $query;
    }
}