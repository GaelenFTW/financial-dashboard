<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchasePayment;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PurchasePaymentController extends Controller
{
    protected function parseDate($raw)
    {
        if (!$raw) return null;
        $raw = trim($raw);

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
        if ($val === null || $val === '') return null;
        
        $s = trim((string) $val);
        
        if (is_numeric($s)) {
            return (float) $s;
        }
        if (strtoupper($s) === 'NULL') {
            return null;
        }
        $dotCount = substr_count($s, '.');
        $commaCount = substr_count($s, ',');
        
        $s = str_replace(' ', '', $s);
        
        if ($dotCount > 1 || ($dotCount >= 1 && $commaCount === 1 && strrpos($s, ',') > strrpos($s, '.'))) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
        else if ($commaCount > 1 || ($commaCount >= 1 && $dotCount === 1 && strrpos($s, '.') > strrpos($s, ','))) {
            $s = str_replace(',', '', $s);
        }
        else if ($commaCount === 1 && $dotCount === 0) {
            $s = str_replace(',', '.', $s);
        }
        else if ($dotCount === 1 && $commaCount === 1) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        }
        
        return is_numeric($s) ? (float) $s : null;
    }

    protected function toInt($val)
    {
        if ($val === null || $val === '') return null;
        return (int) $this->toFloat($val);
    }

    /**
     * Detect and extract month/year patterns from headers
     * Matches patterns like: Jan_2025, Feb_2025, Mar_2025, etc.
     */
    protected function detectMonthYearColumns($headers)
    {
        $monthYearColumns = [];
        
        // Pattern to match: Jan_2025, Feb_2025, etc.
        $pattern = '/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})_(.+)$/';
        
        foreach ($headers as $header) {
            if (preg_match($pattern, $header, $matches)) {
                $month = $matches[1];
                $year = $matches[2];
                $field = $matches[3]; // DueDate, Type, Piutang, CairDate, Payment
                
                $key = "{$month}_{$year}";
                
                if (!isset($monthYearColumns[$key])) {
                    $monthYearColumns[$key] = [];
                }
                
                $monthYearColumns[$key][$field] = $header;
            }
            
            // Also detect YTD columns like YTD_sd_Jun_2025, YTD_bayar_Jun_2025
            if (preg_match('/^(YTD_sd|YTD_bayar)_(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})$/', $header, $matches)) {
                $ytdType = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $key = "{$month}_{$year}";
                
                if (!isset($monthYearColumns[$key])) {
                    $monthYearColumns[$key] = [];
                }
                
                $monthYearColumns[$key][$ytdType] = $header;
            }
            
            // Detect Before columns like Amount_Before_Jan_2025
            if (preg_match('/^(Amount|Piutang|Payment)_Before_(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})$/', $header, $matches)) {
                $field = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $key = "Before_{$month}_{$year}";
                
                if (!isset($monthYearColumns[$key])) {
                    $monthYearColumns[$key] = [];
                }
                
                $monthYearColumns[$key][$field] = $header;
            }
            
            // Detect After columns like Piutang_After_Jun_2025
            if (preg_match('/^(Piutang|Payment)_After_(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})$/', $header, $matches)) {
                $field = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $key = "After_{$month}_{$year}";
                
                if (!isset($monthYearColumns[$key])) {
                    $monthYearColumns[$key] = [];
                }
                
                $monthYearColumns[$key][$field] = $header;
            }
        }
        
        return $monthYearColumns;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'data_year' => 'required|integer|min:2020|max:2100'
        ]);

        $file = $request->file('file');
        $dataYear = $request->input('data_year');
        
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $header = array_shift($rows);
        
        // Detect year from headers (e.g., Jan_2025_Piutang -> 2025)
        $detectedYear = null;
        foreach ($header as $col) {
            if (preg_match('/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})_/', $col, $matches)) {
                $detectedYear = (int)$matches[2];
                break;
            }
        }
        
        // Use detected year or fallback to provided year
        $yearToUse = $detectedYear ?? $dataYear;
        
        \Log::info("Upload: Detected year={$detectedYear}, User selected year={$dataYear}, Using year={$yearToUse}");

        $successCount = 0;
        $errorCount = 0;

        foreach ($rows as $rowIndex => $row) {
            try {
                $data = [];
                foreach ($header as $colKey => $colName) {
                    $data[$colName] = $row[$colKey] ?? null;
                }

                // Build the columns to insert/update
                $columns = [
                    'No'                        => $this->toFloat($data['No'] ?? null),
                    'is_reportcashin'           => $this->toFloat($data['is_reportcashin'] ?? null),
                    'Cluster'                   => $data['Cluster'] ?? null,
                    'Block'                     => $data['Block'] ?? null,
                    'Unit'                      => $data['Unit'] ?? null,
                    'CustomerName'              => $data['CustomerName'] ?? null,
                    'PurchaseDate'              => $this->parseDate($data['PurchaseDate'] ?? null),
                    'LunasDate'                 => $this->parseDate($data['LunasDate'] ?? null),
                    'is_ppndtp'                 => $this->toFloat($data['is_ppndtp'] ?? null),
                    'persen_ppndtp'             => $this->toFloat($data['persen_ppndtp'] ?? null),
                    'harga_netto'               => $this->toFloat($data['harga_netto'] ?? null),
                    'TotalPPN'                  => $this->toFloat($data['TotalPPN'] ?? null),
                    'harga_bbnsertifikat'       => $this->toFloat($data['harga_bbnsertifikat'] ?? null),
                    'harga_bajb'                => $this->toFloat($data['harga_bajb'] ?? null),
                    'harga_bphtb'               => $this->toFloat($data['harga_bphtb'] ?? null),
                    'harga_administrasi'        => $this->toFloat($data['harga_administrasi'] ?? null),
                    'harga_paket_tambahan'      => $this->toFloat($data['harga_paket_tambahan'] ?? null),
                    'harga_admsubsidi'          => $this->toFloat($data['harga_admsubsidi'] ?? null),
                    'biaya_asuransi'            => $this->toFloat($data['biaya_asuransi'] ?? null),
                    'HrgJualTotal'              => $this->toFloat($data['HrgJualTotal'] ?? null),
                    'disc_collection'           => $this->toFloat($data['disc_collection'] ?? null),
                    'HrgJualTotalminDiscColl'   => $this->toFloat($data['HrgJualTotalminDiscColl'] ?? null),
                    'TypePembelian'             => $data['TypePembelian'] ?? null,
                    'bank_induk'                => $data['bank_induk'] ?? null,
                    'KPP'                       => $data['KPP'] ?? null,
                    'JenisKPR'                  => $data['JenisKPR'] ?? null,
                    'Member'                    => $data['Member'] ?? null,
                    'Salesman'                  => $data['Salesman'] ?? null,
                    'tanggal_akad'              => $this->parseDate($data['tanggal_akad'] ?? null),
                    'persen_progress_bangun'    => $this->toFloat($data['persen_progress_bangun'] ?? null),
                    'type_unit'                 => $data['type_unit'] ?? null,
                    'selisih'                   => $this->toFloat($data['selisih'] ?? null),
                    'dari_1_sampai_30_DP'       => $this->toFloat($data['dari_1_sampai_30_DP'] ?? null),
                    'dari_31_sampai_60_DP'      => $this->toFloat($data['dari_31_sampai_60_DP'] ?? null),
                    'dari_61_sampai_90_DP'      => $this->toFloat($data['dari_61_sampai_90_DP'] ?? null),
                    'diatas_90_DP'              => $this->toFloat($data['diatas_90_DP'] ?? null),
                    'lebih_bayar'               => $this->toFloat($data['lebih_bayar'] ?? null),
                    'helper_tahun'              => $this->toInt($data['helper_tahun'] ?? null),
                ];

                // Add dynamic month/year columns
                foreach ($dynamicColumns as $key => $fields) {
                    foreach ($fields as $fieldType => $excelColumn) {
                        if (isset($data[$excelColumn])) {
                            // Map to database column names
                            $dbColumn = $excelColumn;
                            
                            if ($fieldType === 'DueDate' || $fieldType === 'CairDate') {
                                $columns[$dbColumn] = $this->parseDate($data[$excelColumn]);
                            } elseif ($fieldType === 'Type') {
                                $columns[$dbColumn] = $data[$excelColumn];
                            } else {
                                // Piutang, Payment, Amount, YTD_sd, YTD_bayar
                                $columns[$dbColumn] = $this->toFloat($data[$excelColumn]);
                            }
                        }
                    }
                }

                PurchasePayment::updateOrCreate(
                    ['purchaseletter_id' => $data['purchaseletter_id']],
                    $columns
                );
                
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                \Log::error("Error importing row " . ($rowIndex + 2) . ": " . $e->getMessage());
            }
        }

        $message = "Upload completed: {$successCount} records successful";
        if ($errorCount > 0) {
            $message .= ", {$errorCount} records failed. Check logs for details.";
        }

        return redirect()->back()->with('success', $message);
    }
    
    public function uploadForm()
    {
        return view('payments.upload');
    }

    public function view(Request $request)
    {
        $query = PurchasePayment::query();
        
        // Apply filters
        if ($request->filled('cluster')) {
            $query->where('Cluster', 'like', '%' . $request->cluster . '%');
        }

        if ($request->filled('block')) {
            $query->where('Block', 'like', '%' . $request->block . '%');
        }

        if ($request->filled('unit')) {
            $query->where('Unit', 'like', '%' . $request->unit . '%');
        }

        if ($request->filled('customername')) {
            $query->where('CustomerName', 'like', '%' . $request->customername . '%');
        }

        if ($request->filled('typepembelian')) {
            $query->where('TypePembelian', 'like', '%' . $request->typepembelian . '%');
        }

        if ($request->filled('type_unit')) {
            $query->where('type_unit', 'like', '%' . $request->type_unit . '%');
        }

        if ($request->filled('salesman')) {
            $query->where('Salesman', 'like', '%' . $request->salesman . '%');
        }

        if ($request->filled('startdate')) {
            $query->whereDate('PurchaseDate', '>=', $request->startdate);
        }

        if ($request->filled('enddate')) {
            $query->whereDate('PurchaseDate', '<=', $request->enddate);
        }

        $payments = $query->orderBy('PurchaseDate', 'desc')->paginate(20);
        
        return view('payments.view', [
            'payments' => $payments,
            'filters' => $request->all()
        ]);
    }

    public function export(Request $request)
    {
        $query = PurchasePayment::query();
        
        // Apply same filters as view
        if ($request->filled('cluster')) {
            $query->where('Cluster', 'like', '%' . $request->cluster . '%');
        }

        if ($request->filled('block')) {
            $query->where('Block', 'like', '%' . $request->block . '%');
        }

        if ($request->filled('unit')) {
            $query->where('Unit', 'like', '%' . $request->unit . '%');
        }

        if ($request->filled('customername')) {
            $query->where('CustomerName', 'like', '%' . $request->customername . '%');
        }

        if ($request->filled('typepembelian')) {
            $query->where('TypePembelian', 'like', '%' . $request->typepembelian . '%');
        }

        if ($request->filled('type_unit')) {
            $query->where('type_unit', 'like', '%' . $request->type_unit . '%');
        }

        if ($request->filled('salesman')) {
            $query->where('Salesman', 'like', '%' . $request->salesman . '%');
        }

        if ($request->filled('startdate')) {
            $query->whereDate('PurchaseDate', '>=', $request->startdate);
        }

        if ($request->filled('enddate')) {
            $query->whereDate('PurchaseDate', '<=', $request->enddate);
        }

        $payments = $query->get();

        if ($payments->isEmpty()) {
            return redirect()->back()->with('error', 'No data to export');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Get column names dynamically
        $firstRecord = $payments->first();
        $columns = array_keys($firstRecord->getAttributes());

        // Set headers
        foreach ($columns as $i => $heading) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $heading);
        }

        // Fill data
        $rowIndex = 2;
        foreach ($payments as $payment) {
            foreach ($columns as $i => $colName) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $value = $payment->$colName ?? '';
                $sheet->setCellValue($col . $rowIndex, $value);
            }
            $rowIndex++;
        }

        $fileName = 'purchase_payments_' . date('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'payment_export_');
        $writer->save($tmp);

        return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
    }
}