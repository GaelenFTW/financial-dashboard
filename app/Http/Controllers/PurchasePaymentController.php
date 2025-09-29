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
     * Generate dynamic month columns based on user-selected date
     */
    protected function generateMonthColumns($endDate, $data)
    {
        $columns = [];
        $targetDate = Carbon::parse($endDate);
        $monthIndex = 1;

        // Generate columns from month 1 up to the target month
        $currentDate = Carbon::parse($targetDate)->startOfMonth();
        
        while ($monthIndex <= $targetDate->month || ($monthIndex <= 12 && $targetDate->month == 12)) {
            $monthKey = str_pad($monthIndex, 2, '0', STR_PAD_LEFT);
            
            $columns["{$monthKey}_tahun_DueDate"] = $this->parseDate($data["{$monthKey}_tahun_DueDate"] ?? null);
            $columns["{$monthKey}_tahun_Type"] = $data["{$monthKey}_tahun_Type"] ?? null;
            $columns["{$monthKey}_tahun_Piutang"] = $this->toFloat($data["{$monthKey}_tahun_Piutang"] ?? null);
            $columns["{$monthKey}_tahun_CairDate"] = $this->parseDate($data["{$monthKey}_tahun_CairDate"] ?? null);
            $columns["{$monthKey}_tahun_Payment"] = $this->toFloat($data["{$monthKey}_tahun_Payment"] ?? null);
            
            // Only add YTD columns for the last month
            if ($monthIndex == $targetDate->month) {
                $columns["Piutang_After_{$monthKey}_tahun"] = $this->toFloat($data["Piutang_After_{$monthKey}_tahun"] ?? null);
                $columns["Payment_After_{$monthKey}_tahun"] = $this->toFloat($data["Payment_After_{$monthKey}_tahun"] ?? null);   
                $columns["YTD_sd_{$monthKey}_tahun"] = $this->toFloat($data["YTD_sd_{$monthKey}_tahun"] ?? null);
                $columns["YTD_bayar_{$monthKey}_tahun"] = $this->toFloat($data["YTD_bayar_{$monthKey}_tahun"] ?? null);
            }
            
            $monthIndex++;
            
            // Stop at the target month
            if ($monthIndex > $targetDate->month) {
                break;
            }
        }

        return $columns;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'end_date' => 'required|date' // User must provide the target date
        ]);

        $file = $request->file('file');
        $endDate = $request->input('end_date');
        
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $header = array_shift($rows);

        $successCount = 0;
        $errorCount = 0;

        foreach ($rows as $rowIndex => $row) {
            try {
                $data = [];
                foreach ($header as $colKey => $colName) {
                    $data[$colName] = $row[$colKey] ?? null;
                }

                // Static columns
                $staticColumns = [
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
                    'Amount_Before_01_tahun'    => $this->toFloat($data['Amount_Before_01_tahun'] ?? null),
                    'Piutang_Before_01_tahun'   => $this->toFloat($data['Piutang_Before_01_tahun'] ?? null),
                    'Payment_Before_01_tahun'   => $this->toFloat($data['Payment_Before_01_tahun'] ?? null),
                ];

                // Generate dynamic month columns
                $dynamicColumns = $this->generateMonthColumns($endDate, $data);

                // After month columns
                $afterColumns = [

                    'selisih'                   => $this->toFloat($data['selisih'] ?? null),
                    'dari_1_sampai_30_DP'       => $this->toFloat($data['dari_1_sampai_30_DP'] ?? null),
                    'dari_31_sampai_60_DP'      => $this->toFloat($data['dari_31_sampai_60_DP'] ?? null),
                    'dari_61_sampai_90_DP'      => $this->toFloat($data['dari_61_sampai_90_DP'] ?? null),
                    'diatas_90_DP'              => $this->toFloat($data['diatas_90_DP'] ?? null),
                    'lebih_bayar'               => $this->toFloat($data['lebih_bayar'] ?? null),
                    'helper_tahun'              => $this->toInt($data['helper_tahun'] ?? null),
                ];

                // Merge all columns
                $allColumns = array_merge($staticColumns, $dynamicColumns, $afterColumns);

                PurchasePayment::updateOrCreate(
                    ['purchaseletter_id' => $data['purchaseletter_id']],
                    $allColumns
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

    public function view()
    {
        $payments = PurchasePayment::paginate(20);
        return view('payments.view', compact('payments'));
    }
}