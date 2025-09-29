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


    protected function generateMonthColumns($endDate, $data)
{
    $columns = [];
    $targetDate = Carbon::parse($endDate);

    // Start from January of the target year
    $currentDate = Carbon::create($targetDate->year, 1, 1);

    while ($currentDate->lte($targetDate)) {
        $monthYear = $currentDate->format('M_Y'); // e.g. Jan_2025

        // Map Excel columns
        $columns["{$monthYear}_DueDate"]  = $this->parseDate($data["{$monthYear}_DueDate"] ?? null);
        $columns["{$monthYear}_Type"]     = $data["{$monthYear}_Type"] ?? null;
        $columns["{$monthYear}_Piutang"]  = $this->toFloat($data["{$monthYear}_Piutang"] ?? null);
        $columns["{$monthYear}_CairDate"] = $this->parseDate($data["{$monthYear}_CairDate"] ?? null);
        $columns["{$monthYear}_Payment"]  = $this->toFloat($data["{$monthYear}_Payment"] ?? null);

        // Add YTD / After columns only for the final month
        if ($currentDate->month == $targetDate->month) {
            $columns["Piutang_After_{$monthYear}"] = $this->toFloat($data["Piutang_After_{$monthYear}"] ?? null);
            $columns["Payment_After_{$monthYear}"] = $this->toFloat($data["Payment_After_{$monthYear}"] ?? null);
            $columns["YTD_sd_{$monthYear}"]        = $this->toFloat($data["YTD_sd_{$monthYear}"] ?? null);
            $columns["YTD_bayar_{$monthYear}"]     = $this->toFloat($data["YTD_bayar_{$monthYear}"] ?? null);
        }

        $currentDate->addMonth();
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
                'purchaseletter_id'          => $data['purchaseletter_id'] ?? null, // ✅ added
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

            // Insert/Update into dbo.purchase_payments
            PurchasePayment::updateOrCreate(
                ['purchaseletter_id' => $staticColumns['purchaseletter_id']],
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