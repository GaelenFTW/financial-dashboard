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
            $s = str_replace('.', '', $s);  // Remove thousand separators
            $s = str_replace(',', '.', $s); // Convert decimal separator
        }
        else if ($commaCount > 1 || ($commaCount >= 1 && $dotCount === 1 && strrpos($s, '.') > strrpos($s, ','))) {
            $s = str_replace(',', '', $s);  // Remove thousand separators
        }
        // Single comma - treat as decimal separator
        else if ($commaCount === 1 && $dotCount === 0) {
            $s = str_replace(',', '.', $s);
        }
        // Single dot with comma - ambiguous, assume Indonesian format
        else if ($dotCount === 1 && $commaCount === 1) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastComma > $lastDot) {
                // Format: 1.234,56
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // Format: 1,234.56
                $s = str_replace(',', '', $s);
            }
        }
        
        return is_numeric($s) ? (float) $s : null;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // Take first row as header
        $header = array_shift($rows);

        $successCount = 0;
        $errorCount = 0;

        foreach ($rows as $rowIndex => $row) {
            try {
                $data = [];
                foreach ($header as $colKey => $colName) {
                    $data[$colName] = $row[$colKey] ?? null;
                }

                // Save/update
                PurchasePayment::updateOrCreate(
                    ['purchaseletter_id' => $data['purchaseletter_id']],
                    [
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
                        '01_tahun_DueDate'          => $this->parseDate($data['01_tahun_DueDate'] ?? null),
                        '01_tahun_Type'             => $data['01_tahun_Type'] ?? null,
                        '01_tahun_Piutang'          => $this->toFloat($data['01_tahun_Piutang'] ?? null),
                        '01_tahun_CairDate'         => $this->parseDate($data['01_tahun_CairDate'] ?? null),
                        '01_tahun_Payment'          => $this->toFloat($data['01_tahun_Payment'] ?? null),
                        '02_tahun_DueDate'          => $this->parseDate($data['02_tahun_DueDate'] ?? null),
                        '02_tahun_Type'             => $data['02_tahun_Type'] ?? null,
                        '02_tahun_Piutang'          => $this->toFloat($data['02_tahun_Piutang'] ?? null),
                        '02_tahun_CairDate'         => $this->parseDate($data['02_tahun_CairDate'] ?? null),
                        '02_tahun_Payment'          => $this->toFloat($data['02_tahun_Payment'] ?? null),
                        '03_tahun_DueDate'          => $this->parseDate($data['03_tahun_DueDate'] ?? null),
                        '03_tahun_Type'             => $data['03_tahun_Type'] ?? null,
                        '03_tahun_Piutang'          => $this->toFloat($data['03_tahun_Piutang'] ?? null),
                        '03_tahun_CairDate'         => $this->parseDate($data['03_tahun_CairDate'] ?? null),
                        '03_tahun_Payment'          => $this->toFloat($data['03_tahun_Payment'] ?? null),
                        '04_tahun_DueDate'          => $this->parseDate($data['04_tahun_DueDate'] ?? null),
                        '04_tahun_Type'             => $data['04_tahun_Type'] ?? null,
                        '04_tahun_Piutang'          => $this->toFloat($data['04_tahun_Piutang'] ?? null),
                        '04_tahun_CairDate'         => $this->parseDate($data['04_tahun_CairDate'] ?? null),
                        '04_tahun_Payment'          => $this->toFloat($data['04_tahun_Payment'] ?? null),
                        '05_tahun_DueDate'          => $this->parseDate($data['05_tahun_DueDate'] ?? null),
                        '05_tahun_Type'             => $data['05_tahun_Type'] ?? null,
                        '05_tahun_Piutang'          => $this->toFloat($data['05_tahun_Piutang'] ?? null),
                        '05_tahun_CairDate'         => $this->parseDate($data['05_tahun_CairDate'] ?? null),
                        '05_tahun_Payment'          => $this->toFloat($data['05_tahun_Payment'] ?? null),
                        'Piutang_After_05_tahun'    => $this->toFloat($data['Piutang_After_05_tahun'] ?? null),
                        'Payment_After_05_tahun'    => $this->toFloat($data['Payment_After_05_tahun'] ?? null),
                        'YTD_sd_05_tahun'           => $this->toFloat($data['YTD_sd_05_tahun'] ?? null),
                        'YTD_bayar_05_tahun'        => $this->toFloat($data['YTD_bayar_05_tahun'] ?? null),
                        '06_tahun_Type'             => $data['06_tahun_Type'] ?? null,
                        '06_tahun_Piutang'          => $this->toFloat($data['06_tahun_Piutang'] ?? null),
                        '06_tahun_CairDate'         => $this->parseDate($data['06_tahun_CairDate'] ?? null),
                        '06_tahun_Payment'          => $this->toFloat($data['06_tahun_Payment'] ?? null),
                        '06_tahun_DueDate'          => $this->parseDate($data['06_tahun_DueDate'] ?? null),
                        '07_tahun_Type'             => $data['07_tahun_Type'] ?? null,
                        '07_tahun_Piutang'          => $this->toFloat($data['07_tahun_Piutang'] ?? null),
                        '07_tahun_CairDate'         => $this->parseDate($data['07_tahun_CairDate'] ?? null),
                        '07_tahun_Payment'          => $this->toFloat($data['07_tahun_Payment'] ?? null),
                        'selisih'                   => $this->toFloat($data['selisih'] ?? null),
                        'dari_1_sampai_30_DP'       => $this->toFloat($data['dari_1_sampai_30_DP'] ?? null),
                        'dari_31_sampai_60_DP'      => $this->toFloat($data['dari_31_sampai_60_DP'] ?? null),
                        'dari_61_sampai_90_DP'      => $this->toFloat($data['dari_61_sampai_90_DP'] ?? null),
                        'diatas_90_DP'              => $this->toFloat($data['diatas_90_DP'] ?? null),
                        'lebih_bayar'               => $this->toFloat($data['lebih_bayar'] ?? null),
                        'helper_tahun'              => $this->toInt($data['helper_tahun'] ?? null),
                    ]
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