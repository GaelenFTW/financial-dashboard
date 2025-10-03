<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchasePayment;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class PurchasePaymentController extends Controller
{
    protected function parseDate($raw)
    {
        if ($raw === null || $raw === '') return null;

        // If it's already a Carbon instance or DateTime, return as Carbon
        if ($raw instanceof Carbon) return $raw;
        if ($raw instanceof \DateTime) return Carbon::instance($raw);

        $raw = trim((string)$raw);

        // Remove common excel timezone suffix or milliseconds formatting if present
        $raw = preg_replace('/\.0+$/', '', $raw);

        // Common formats to try first
        $formats = [
            'd-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d',
            'd-m-Y H:i:s', 'Y-m-d H:i:s', 'd/m/Y H:i:s', 'Y/m/d H:i:s',
            'd-m-Y H:i', 'Y-m-d H:i', 'd/m/Y H:i', 'Y/m/d H:i'
        ];

        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $raw);
                if ($dt !== false) return $dt;
            } catch (\Exception $e) {
                // continue
            }
        }

        // Fallback to strtotime (handles many localized formats)
        $ts = strtotime(str_replace('-', '/', $raw));
        if ($ts !== false) {
            return Carbon::createFromTimestamp($ts);
        }

        return null;
    }

    protected function toFloat($val)
    {
        if ($val === null || $val === '') return null;

        // Keep numeric and negative parentheses support
        $s = trim((string)$val);

        // Remove non-breaking spaces
        $s = str_replace("\xc2\xa0", '', $s);

        // Convert (123) to -123
        if (preg_match('/^\((.*)\)$/', $s, $m)) {
            $s = '-' . $m[1];
        }

        // If already numeric-ish
        if (is_numeric($s)) {
            return (float)$s;
        }

        // If textual "NULL"
        if (strtoupper($s) === 'NULL') return null;

        // Remove spaces
        $s = str_replace(' ', '', $s);

        // Count separators
        $dotCount = substr_count($s, '.');
        $commaCount = substr_count($s, ',');

        // If both exist, decide which is decimal by position
        if ($dotCount > 0 && $commaCount > 0) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastComma > $lastDot) {
                // comma is decimal
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // dot is decimal
                $s = str_replace(',', '', $s);
            }
        } elseif ($commaCount > 0 && $dotCount === 0) {
            // only comma -> decimal
            $s = str_replace(',', '.', $s);
        } else {
            // remove thousand separators if more than one dot
            if ($dotCount > 1) $s = str_replace('.', '', $s);
        }

        // strip any non numeric/period/minus
        $s = preg_replace('/[^0-9\.\-]/', '', $s);

        return is_numeric($s) ? (float)$s : null;
    }

    protected function toInt($val)
    {
        if ($val === null || $val === '') return null;
        $f = $this->toFloat($val);
        return $f === null ? null : (int)$f;
    }

    /**
     * Normalize single header string: remove BOM, NBSP, trim
     */
    protected function normalizeHeader(string $h): string
    {
        // Remove BOM
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);

        // Replace non-breaking spaces with normal space
        $h = str_replace("\xc2\xa0", ' ', $h);

        // Trim and collapse whitespace
        $h = trim($h);
        $h = preg_replace('/\s+/', ' ', $h);

        // Collapse spaces around underscores and remove accidental trailing/leading underscores
        $h = preg_replace('/\s*_\s*/', '_', $h);
        $h = trim($h, '_');

        // Keep underscores as-is (don't force underscores)
        return $h;
    }

    /**
     * Detect and extract month/year patterns from headers
     * Accepts array of header strings
     */
    protected function detectMonthYearColumns($headers)
    {
        $monthYearColumns = [];

        $pattern = '/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})_(.+)$/i';

        foreach ($headers as $header) {
            $h = (string)$header;
            if (preg_match($pattern, $h, $matches)) {
                $month = $matches[1];
                $year = $matches[2];
                $field = $matches[3];

                $key = "{$month}_{$year}";

                if (!isset($monthYearColumns[$key])) {
                    $monthYearColumns[$key] = [];
                }

                // preserve original header string
                $monthYearColumns[$key][$field] = $h;
            }

            // YTD patterns
            if (preg_match('/^(YTD_sd|YTD_bayar)_(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})$/i', $h, $matches)) {
                $ytdType = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $key = "{$month}_{$year}";

                if (!isset($monthYearColumns[$key])) {
                    $monthYearColumns[$key] = [];
                }

                $monthYearColumns[$key][$ytdType] = $h;
            }

            // Before / After patterns
            if (preg_match('/^(Amount|Piutang|Payment)_Before_(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})$/i', $h, $matches)) {
                $field = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $key = "Before_{$month}_{$year}";
                if (!isset($monthYearColumns[$key])) $monthYearColumns[$key] = [];
                $monthYearColumns[$key][$field] = $h;
            }
            if (preg_match('/^(Piutang|Payment)_After_(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})$/i', $h, $matches)) {
                $field = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $key = "After_{$month}_{$year}";
                if (!isset($monthYearColumns[$key])) $monthYearColumns[$key] = [];
                $monthYearColumns[$key][$field] = $h;
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

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Uploaded file is empty');
        }

        // Extract header row and normalize each header string
        $header = array_shift($rows); // header map: columnLetter => headerString
        $headerCleanMap = []; // columnLetter => cleanedHeaderString
        foreach ($header as $colKey => $colName) {
            $clean = $this->normalizeHeader((string)$colName);
            $headerCleanMap[$colKey] = $clean;
        }

        // Build a simple array of cleaned header strings for detection routines
        $cleanHeadersFlat = array_values($headerCleanMap);

        // Detect year from headers (if present)
        $detectedYear = null;
        foreach ($cleanHeadersFlat as $col) {
            if (preg_match('/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})_/i', $col, $matches)) {
                $detectedYear = (int)$matches[2];
                break;
            }
        }

        // final year choice
        $yearToUse = $detectedYear ?? $dataYear;
        Log::info("Upload: Detected year={$detectedYear}, User selected year={$dataYear}, Using year={$yearToUse}");

        // Build dynamic mapping info from the cleaned headers
        $dynamicColumns = $this->detectMonthYearColumns($cleanHeadersFlat);

        // Get actual DB columns (case-sensitive as returned by DB) and prepare lower-case map
        $tableColumns = Schema::getColumnListing((new PurchasePayment)->getTable());
        $tableColsLowerMap = [];
        foreach ($tableColumns as $c) {
            $tableColsLowerMap[strtolower($c)] = $c;
        }

        $successCount = 0;
        $errorCount = 0;

        // expected per-month subfields
        $monthSubfields = ['DueDate', 'Type', 'Piutang', 'CairDate', 'Payment'];

        foreach ($rows as $rowIndex => $row) {
            try {
                // Build data keyed by cleaned header names (this helps for static fields lookup)
                $data = [];
                foreach ($headerCleanMap as $colKey => $cleanHeader) {
                    $data[$cleanHeader] = $row[$colKey] ?? null;
                }

                // Build base columns using user's original mapping logic (kept)
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
                    // ensure data_year is set from user selection or detected header
                    'data_year'                 => $yearToUse,
                ];

                // Add computed year/month fields derived from PurchaseDate (fallback to data_year)
                if (!empty($columns['PurchaseDate'])) {
                    $parsed = $columns['PurchaseDate'];
                    if ($parsed instanceof Carbon) {
                        $columns['year'] = $parsed->year;
                        $columns['month'] = $parsed->month;
                    } else {
                        try {
                            $dt = Carbon::parse($parsed);
                            $columns['year'] = $dt->year;
                            $columns['month'] = $dt->month;
                        } catch (\Exception $e) {
                            $columns['year'] = $yearToUse;
                            $columns['month'] = null;
                        }
                    }
                } else {
                    $columns['year'] = $yearToUse;
                    $columns['month'] = null;
                }

                // -------------------------
                // Robust mapping for dynamic month/year columns:
                // Look for headers containing month + year + subfield and map their cell values.
                // -------------------------
                foreach ($dynamicColumns as $monthYearKey => $fields) {
                    // monthYearKey e.g. "Jan_2024"
                    $parts = explode('_', $monthYearKey, 2);
                    if (count($parts) < 2) continue;
                    $month = $parts[0];
                    $year = $parts[1];

                    foreach ($monthSubfields as $sub) {
                        $foundColKey = null;
                        $foundHeader = null;

                        // find the column letter that contains month, year and sub (case-insensitive)
                        foreach ($headerCleanMap as $colKey => $cleanHeader) {
                            $h = strtolower($cleanHeader);
                            if (strpos($h, strtolower($month)) !== false
                                && strpos($h, (string)$year) !== false
                                && strpos($h, strtolower($sub)) !== false) {
                                $foundColKey = $colKey;
                                $foundHeader = $cleanHeader;
                                break;
                            }
                        }

                        if (!$foundColKey) {
                            // not found: skip this subfield
                            continue;
                        }

                        $rawVal = $row[$foundColKey] ?? null;
                        if ($rawVal === null || $rawVal === '') {
                            $value = null;
                        } else {
                            if (stripos($sub, 'DueDate') !== false || stripos($sub, 'CairDate') !== false) {
                                $value = $this->parseDate($rawVal);
                            } elseif (stripos($sub, 'Type') !== false) {
                                $value = (string)$rawVal;
                            } else {
                                $value = $this->toFloat($rawVal);
                            }
                        }

                        // Try DB generic name (e.g. Jan_Year_DueDate)
                        $dbGeneric = "{$month}_Year_{$sub}";
                        $lg = strtolower($dbGeneric);

                        if (isset($tableColsLowerMap[$lg])) {
                            $actualDbCol = $tableColsLowerMap[$lg];
                            $columns[$actualDbCol] = $value;
                        } else {
                            // fallback: if DB stores the exact excel header name (with year), map it too
                            $foundHeaderLower = strtolower($foundHeader);
                            if (isset($tableColsLowerMap[$foundHeaderLower])) {
                                $actualDbCol = $tableColsLowerMap[$foundHeaderLower];
                                $columns[$actualDbCol] = $value;
                            } else {
                                // if still not found try a couple of common alternate patterns:
                                // e.g. "Jan_2024_DueDate" (exact) -> check its lower-case presence
                                $exactExcel = "{$month}_{$year}_{$sub}";
                                $exactLower = strtolower($exactExcel);
                                if (isset($tableColsLowerMap[$exactLower])) {
                                    $columns[$tableColsLowerMap[$exactLower]] = $value;
                                } else {
                                    // skip silently if DB doesn't have the column
                                }
                            }
                        }
                    }
                }

                // Also handle Before / After / YTD style headers across all cleaned headers (in case they were not in dynamicColumns)
                foreach ($cleanHeadersFlat as $cleanHeader) {
                    $rawVal = $data[$cleanHeader] ?? null;
                    $hLower = strtolower($cleanHeader);

                    // Amount/Piutang/Payment_Before_Month_Year  => DB: Amount_Before_Month_Year or Amount_Before_Month_Year (but we prefer *_Before_<Month>_Year)
                    if (preg_match('/^(amount|piutang|payment)_before_(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)_(\d{4})$/i', $cleanHeader, $m)) {
                        $field = $m[1];
                        $month = $m[2];
                        // generic db name:
                        $dbGeneric = ucfirst(strtolower($field)) . "_Before_" . $month . "_Year"; // example: Amount_Before_Jan_Year
                        $lg = strtolower($dbGeneric);
                        if (isset($tableColsLowerMap[$lg])) {
                            $columns[$tableColsLowerMap[$lg]] = $this->toFloat($rawVal);
                        } else {
                            $exactLower = strtolower($cleanHeader);
                            if (isset($tableColsLowerMap[$exactLower])) {
                                $columns[$tableColsLowerMap[$exactLower]] = $this->toFloat($rawVal);
                            }
                        }
                    }

                    // Piutang/Payment_After_Month_Year
                    if (preg_match('/^(piutang|payment)_after_(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)_(\d{4})$/i', $cleanHeader, $m)) {
                        $field = $m[1];
                        $month = $m[2];
                        $dbGeneric = ucfirst(strtolower($field)) . "_After_" . $month . "_Year";
                        $lg = strtolower($dbGeneric);
                        if (isset($tableColsLowerMap[$lg])) {
                            $columns[$tableColsLowerMap[$lg]] = $this->toFloat($rawVal);
                        } else {
                            $exactLower = strtolower($cleanHeader);
                            if (isset($tableColsLowerMap[$exactLower])) {
                                $columns[$tableColsLowerMap[$exactLower]] = $this->toFloat($rawVal);
                            }
                        }
                    }

                    // YTD_sd / YTD_bayar
                    if (preg_match('/^(ytd_sd|ytd_bayar)_(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)_(\d{4})$/i', $cleanHeader, $m)) {
                        $ytd = $m[1];
                        $month = $m[2];
                        $dbGeneric = strtoupper($ytd) . "_" . ucfirst(strtolower($month)) . "_Year"; // This builds YTD_SD_Jan_Year which might not match exactly; so fallback to exact cleaned header
                        $exactLower = strtolower($cleanHeader);
                        if (isset($tableColsLowerMap[$exactLower])) {
                            $columns[$tableColsLowerMap[$exactLower]] = $this->toFloat($rawVal);
                        } else {
                            // as last resort try replacing year with _Year_ pattern
                            $tryGeneric = str_replace("_{$yearToUse}", "_Year", $cleanHeader);
                            if (isset($tableColsLowerMap[strtolower($tryGeneric)])) {
                                $columns[$tableColsLowerMap[strtolower($tryGeneric)]] = $this->toFloat($rawVal);
                            }
                        }
                    }
                }

                // Finally, map $columns keys to exact DB column names using case-insensitive matching
                $finalColumns = [];
                foreach ($columns as $k => $v) {
                    $lk = strtolower($k);
                    if (isset($tableColsLowerMap[$lk])) {
                        $finalColumns[$tableColsLowerMap[$lk]] = $v;
                    }
                }

                // Ensure data_year is definitely present if DB has it
                if (isset($tableColsLowerMap['data_year'])) {
                    $finalColumns[$tableColsLowerMap['data_year']] = $yearToUse;
                }

                // Find purchaseletter_id value from cleaned data (case-insensitive)
                $purchaseValue = null;
                foreach ($data as $k => $v) {
                    $lk = strtolower($k);
                    if (in_array($lk, ['purchaseletter_id', 'purchase_letter_id', 'purchaseletterid', 'purchase letter id'])) {
                        $purchaseValue = $v;
                        break;
                    }
                }

                // Figure out the actual DB column name for purchaseletter_id (if it exists)
                $dbPurchaseCol = null;
                foreach (['purchaseletter_id', 'purchase_letter_id', 'purchaseletterid'] as $possible) {
                    if (isset($tableColsLowerMap[$possible])) {
                        $dbPurchaseCol = $tableColsLowerMap[$possible];
                        break;
                    }
                }

                // Perform create/update
                if ($purchaseValue === null || $purchaseValue === '') {
                    // No unique key value provided — create a new row
                    if (!empty($finalColumns)) {
                        PurchasePayment::create($finalColumns);
                    }
                } else {
                    if ($dbPurchaseCol) {
                        PurchasePayment::updateOrCreate(
                            [$dbPurchaseCol => $purchaseValue],
                            $finalColumns
                        );
                    } else {
                        // fallback: try updateOrCreate with plain key
                        PurchasePayment::updateOrCreate(
                            ['purchaseletter_id' => $purchaseValue],
                            $finalColumns
                        );
                    }
                }

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
                // If Carbon, format it
                if ($value instanceof Carbon) {
                    $value = $value->format('Y-m-d H:i:s');
                }
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
