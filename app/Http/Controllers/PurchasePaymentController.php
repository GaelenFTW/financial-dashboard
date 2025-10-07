<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchasePayment;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'data_year' => 'required|integer|min:2020|max:2100',
            'data_month' => 'required|integer|min:1|max:12',
            'project_id' => 'required|integer',
        ]);

        $file = $request->file('file');
        $dataYear = $request->input('data_year');
        $dataMonth = $request->input('data_month');
        $projectId = $request->input('project_id');
        
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $header = array_shift($rows);
        
        // Detect year from headers
        $detectedYear = null;
        foreach ($header as $col) {
            if (preg_match('/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})_/', $col, $matches)) {
                $detectedYear = (int)$matches[2];
                break;
            }
        }
        
        $yearToUse = $detectedYear ?? $dataYear;
        Log::info("Upload: Detected year={$detectedYear}, User selected year={$dataYear}, Using year={$yearToUse}");

        // Build column mappings
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $mappings = [];
        
        // Before columns
        $mappings["Amount_Before_Jan_{$yearToUse}"] = "Amount_Before_Jan_Year";
        $mappings["Piutang_Before_Jan_{$yearToUse}"] = "Piutang_Before_Jan_Year";
        $mappings["Payment_Before_Jan_{$yearToUse}"] = "Payment_Before_Jan_Year";
        
        // Monthly columns
        foreach ($monthNames as $month) {
            $mappings["{$month}_{$yearToUse}_DueDate"] = "{$month}_Year_DueDate";
            $mappings["{$month}_{$yearToUse}_Type"] = "{$month}_Year_Type";
            $mappings["{$month}_{$yearToUse}_Piutang"] = "{$month}_Year_Piutang";
            $mappings["{$month}_{$yearToUse}_CairDate"] = "{$month}_Year_CairDate";
            $mappings["{$month}_{$yearToUse}_Payment"] = "{$month}_Year_Payment";
        }
        
        // After & YTD columns - detect last month from Excel
        foreach ($header as $col) {
            if (preg_match('/Piutang_After_([A-Za-z]+)_' . $yearToUse . '/', $col)) {
                $mappings[$col] = "Piutang_After_Year";
            }
            if (preg_match('/Payment_After_([A-Za-z]+)_' . $yearToUse . '/', $col)) {
                $mappings[$col] = "Payment_After_Year";
            }
            if (preg_match('/YTD_sd_([A-Za-z]+)_' . $yearToUse . '/', $col)) {
                $mappings[$col] = "YTD_sd_Year";
            }
            if (preg_match('/YTD_bayar_([A-Za-z]+)_' . $yearToUse . '/', $col)) {
                $mappings[$col] = "YTD_bayar_Year";
            }
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($rows as $rowIndex => $row) {
            try {
                $data = [];
                foreach ($header as $colKey => $colName) {
                    $data[$colName] = $row[$colKey] ?? null;
                }

                // Build columns with mapping
                $columns = [
                    'purchaseletter_id' => $data['purchaseletter_id'] ?? null,
                    'No' => $this->toFloat($data['No'] ?? null),
                    'is_reportcashin' => $this->toFloat($data['is_reportcashin'] ?? null),
                    'Cluster' => $data['Cluster'] ?? null,
                    'Block' => $data['Block'] ?? null,
                    'Unit' => $data['Unit'] ?? null,
                    'CustomerName' => $data['CustomerName'] ?? null,
                    'PurchaseDate' => $this->parseDate($data['PurchaseDate'] ?? null),
                    'LunasDate' => $this->parseDate($data['LunasDate'] ?? null),
                    'is_ppndtp' => $this->toFloat($data['is_ppndtp'] ?? null),
                    'persen_ppndtp' => $this->toFloat($data['persen_ppndtp'] ?? null),
                    'harga_netto' => $this->toFloat($data['harga_netto'] ?? null),
                    'TotalPPN' => $this->toFloat($data['TotalPPN'] ?? null),
                    'harga_bbnsertifikat' => $this->toFloat($data['harga_bbnsertifikat'] ?? null),
                    'harga_bajb' => $this->toFloat($data['harga_bajb'] ?? null),
                    'harga_bphtb' => $this->toFloat($data['harga_bphtb'] ?? null),
                    'harga_administrasi' => $this->toFloat($data['harga_administrasi'] ?? null),
                    'harga_paket_tambahan' => $this->toFloat($data['harga_paket_tambahan'] ?? null),
                    'harga_admsubsidi' => $this->toFloat($data['harga_admsubsidi'] ?? null),
                    'biaya_asuransi' => $this->toFloat($data['biaya_asuransi'] ?? null),
                    'HrgJualTotal' => $this->toFloat($data['HrgJualTotal'] ?? null),
                    'disc_collection' => $this->toFloat($data['disc_collection'] ?? null),
                    'HrgJualTotalminDiscColl' => $this->toFloat($data['HrgJualTotalminDiscColl'] ?? null),
                    'TypePembelian' => $data['TypePembelian'] ?? null,
                    'bank_induk' => $data['bank_induk'] ?? null,
                    'KPP' => $data['KPP'] ?? null,
                    'JenisKPR' => $data['JenisKPR'] ?? null,
                    'Member' => $data['Member'] ?? null,
                    'Salesman' => $data['Salesman'] ?? null,
                    'tanggal_akad' => $this->parseDate($data['tanggal_akad'] ?? null),
                    'persen_progress_bangun' => $this->toFloat($data['persen_progress_bangun'] ?? null),
                    'type_unit' => $data['type_unit'] ?? null,
                    'selisih' => $this->toFloat($data['selisih'] ?? null),
                    'dari_1_sampai_30_DP' => $this->toFloat($data['dari_1_sampai_30_DP'] ?? null),
                    'dari_31_sampai_60_DP' => $this->toFloat($data['dari_31_sampai_60_DP'] ?? null),
                    'dari_61_sampai_90_DP' => $this->toFloat($data['dari_61_sampai_90_DP'] ?? null),
                    'diatas_90_DP' => $this->toFloat($data['diatas_90_DP'] ?? null),
                    'lebih_bayar' => $this->toFloat($data['lebih_bayar'] ?? null),
                    'data_year' => $yearToUse,
                    'data_month' => $dataMonth,
                    'project_id' => $projectId,
                ];

                // Apply mappings
                foreach ($mappings as $excelCol => $dbCol) {
                    if (isset($data[$excelCol])) {
                        if (strpos($dbCol, 'Date') !== false) {
                            $columns[$dbCol] = $this->parseDate($data[$excelCol]);
                        } elseif (strpos($dbCol, 'Type') !== false) {
                            $columns[$dbCol] = $data[$excelCol];
                        } else {
                            $columns[$dbCol] = $this->toFloat($data[$excelCol]);
                        }
                    }
                }

                // Auto-create missing columns
                $existingColumns = DB::connection('sqlsrv')
                    ->getSchemaBuilder()
                    ->getColumnListing('purchase_payments');
                
                foreach (array_keys($columns) as $colName) {
                    if (!in_array($colName, $existingColumns)) {
                        try {
                            if (strpos($colName, 'Date') !== false || strpos($colName, 'date') !== false) {
                                $type = 'datetime2';
                            } elseif (strpos($colName, 'Type') !== false || in_array($colName, ['Cluster', 'Block', 'Unit', 'CustomerName', 'TypePembelian', 'bank_induk', 'KPP', 'JenisKPR', 'Member', 'Salesman', 'type_unit'])) {
                                $type = 'nvarchar(255)';
                            } elseif (in_array($colName, ['data_year', 'data_month', 'project_id'])) {
                                $type = 'int';
                            } else {
                                $type = 'decimal(20,2)';
                            }
                            
                            DB::connection('sqlsrv')->statement(
                                "ALTER TABLE purchase_payments ADD [{$colName}] {$type} NULL"
                            );
                            Log::info("Added column: {$colName} ({$type})");
                        } catch (\Exception $e) {
                            Log::warning("Could not add column {$colName}: " . $e->getMessage());
                        }
                    }
                }

                DB::connection('sqlsrv')
                    ->table('purchase_payments')
                    ->updateOrInsert(
                        ['purchaseletter_id' => $columns['purchaseletter_id']],
                        $columns
                    );

                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Error importing row " . ($rowIndex + 2) . ": " . $e->getMessage());
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
        
        // Apply filters (your existing filter logic)
        if ($request->filled('cluster')) {
            $query->where('Cluster', 'like', '%' . $request->cluster . '%');
        }
        // ... rest of filters

        $payments = $query->orderBy('PurchaseDate', 'desc')->paginate(20);
        
        return view('payments.view', [
            'payments' => $payments,
            'filters' => $request->all()
        ]);
    }
}