<?php

namespace App\Http\Controllers;

use App\Models\PurchasePayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PurchasePaymentController extends Controller
{
    protected $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    protected function parseDate($raw)
    {
        if (! $raw) {
            return null;
        }
        $raw = trim($raw);
        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd-m-Y H:i:s', 'Y-m-d H:i:s'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $raw);
            } catch (\Exception $e) {
            }
        }
        $ts = strtotime(str_replace('-', '/', $raw));

        return $ts ? Carbon::createFromTimestamp($ts) : null;
    }

    protected function toFloat($v)
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = trim((string) $v);
        if (is_numeric($s)) {
            return (float) $s;
        }
        if (strtoupper($s) === 'NULL') {
            return null;
        }
        $dot = substr_count($s, '.');
        $comma = substr_count($s, ',');
        $s = str_replace(' ', '', $s);
        if ($dot > 1 || ($dot >= 1 && $comma === 1 && strrpos($s, ',') > strrpos($s, '.'))) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif ($comma > 1 || ($comma >= 1 && $dot === 1 && strrpos($s, '.') > strrpos($s, ','))) {
            $s = str_replace(',', '', $s);
        } elseif ($comma === 1 && $dot === 0) {
            $s = str_replace(',', '.', $s);
        } elseif ($dot === 1 && $comma === 1) {
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        }

        return is_numeric($s) ? (float) $s : null;
    }

    protected function toInt($v)
    {
        return ($v === null || $v === '') ? null : (int) $this->toFloat($v);
    }

    public function upload(Request $r)
    {
        $r->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'data_year' => 'required|integer|min:2020|max:2100',
            'data_month' => 'required|integer|min:1|max:12',
            'project_id' => 'required|integer',
        ]);

        $file = $r->file('file');
        $year = $r->data_year;
        $month = $r->data_month;
        $project = $r->project_id;
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, true);
        $header = array_shift($rows);
        $detectedYear = null;
        foreach ($header as $col) {
            if (preg_match('/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)_(\d{4})_/', $col, $m)) {
                $detectedYear = (int) $m[2];
                break;
            }
        }
        $yearToUse = $detectedYear ?? $year;
        Log::info("Upload year={$yearToUse}, month={$month}, project={$project}");

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $map = [];
        $map["Amount_Before_Jan_{$yearToUse}"] = 'Amount_Before_Jan_Year';
        $map["Piutang_Before_Jan_{$yearToUse}"] = 'Piutang_Before_Jan_Year';
        $map["Payment_Before_Jan_{$yearToUse}"] = 'Payment_Before_Jan_Year';
        foreach ($months as $m) {
            foreach (['DueDate', 'Type', 'Piutang', 'CairDate', 'Payment'] as $suf) {
                $map["{$m}_{$yearToUse}_{$suf}"] = "{$m}_Year_{$suf}";
            }
        }
        foreach ($header as $c) {
            if (preg_match('/Piutang_After_([A-Za-z]+)_'.$yearToUse.'/', $c)) {
                $map[$c] = 'Piutang_After_Year';
            }
            if (preg_match('/Payment_After_([A-Za-z]+)_'.$yearToUse.'/', $c)) {
                $map[$c] = 'Payment_After_Year';
            }
            if (preg_match('/YTD_sd_([A-Za-z]+)_'.$yearToUse.'/', $c)) {
                $map[$c] = 'YTD_sd_Year';
            }
            if (preg_match('/YTD_bayar_([A-Za-z]+)_'.$yearToUse.'/', $c)) {
                $map[$c] = 'YTD_bayar_Year';
            }
        }

        // Get user identifier
        $user = auth()->user();
        $userIdentifier = $user ? ($user->email ?? 'system') : 'system';

        $ok = $fail = 0;
        foreach ($rows as $i => $row) {
            try {
                $data = [];
                foreach ($header as $k => $v) {
                    $data[$v] = $row[$k] ?? null;
                }
                $cols = [
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
                    'data_month' => $month,
                    'project_id' => $project,
                    'updated_at' => now(),
                    'updated_by' => $userIdentifier,
                ];

                // Map dynamic year columns
                foreach ($map as $excel => $db) {
                    if (isset($data[$excel])) {
                        $cols[$db] = strpos($db, 'Date') !== false ? $this->parseDate($data[$excel]) : (strpos($db, 'Type') !== false ? $data[$excel] : $this->toFloat($data[$excel]));
                    }
                }

                // Ensure columns exist in database
                $existing = DB::connection('sqlsrv')->getSchemaBuilder()->getColumnListing('purchase_payments');
                foreach (array_keys($cols) as $col) {
                    if (! in_array($col, $existing)) {
                        try {
                            $type = str_contains($col, 'Date') ? 'datetime2' : (
                                str_contains($col, 'Type') || in_array($col, ['Cluster', 'Block', 'Unit', 'CustomerName', 'TypePembelian', 'bank_induk', 'KPP', 'JenisKPR', 'Member', 'Salesman', 'type_unit', 'created_by', 'updated_by']) ? 'nvarchar(255)' : (
                                    in_array($col, ['data_year', 'data_month', 'project_id']) ? 'int' : 'decimal(20,2)'
                                )
                            );
                            DB::connection('sqlsrv')->statement("ALTER TABLE purchase_payments ADD [{$col}] {$type} NULL");
                            Log::info("Column {$col} added successfully");
                        } catch (\Exception $e) {
                            Log::warning("Column {$col}: ".$e->getMessage());
                        }
                    }
                }

                $matchingCriteria = [
                    'purchaseletter_id' => $cols['purchaseletter_id'],
                    'data_year' => $yearToUse,
                    'data_month' => $month,
                    'project_id' => $project,
                ];

                // Remove matching fields from update data
                $updateData = $cols;
                unset($updateData['purchaseletter_id'], $updateData['data_year'], $updateData['data_month'], $updateData['project_id']);

                // Check if record exists with the same combination
                $existingRecord = DB::connection('sqlsrv')->table('purchase_payments')
                    ->where($matchingCriteria)
                    ->first();

                if ($existingRecord) {
                    // UPDATE: Record exists with same purchaseletter_id, year, month, and project
                    DB::connection('sqlsrv')->table('purchase_payments')
                        ->where($matchingCriteria)
                        ->update($updateData);
                    Log::info("Updated record: purchaseletter_id={$cols['purchaseletter_id']}, year={$yearToUse}, month={$month}, project={$project}");
                } else {
                    // INSERT: New combination, create new row
                    $updateData['created_at'] = now();
                    $updateData['created_by'] = $userIdentifier;
                    DB::connection('sqlsrv')->table('purchase_payments')
                        ->insert(array_merge($matchingCriteria, $updateData));
                    Log::info("Inserted new record: purchaseletter_id={$cols['purchaseletter_id']}, year={$yearToUse}, month={$month}, project={$project}");
                }

                $ok++;
            } catch (\Exception $e) {
                $fail++;
                Log::error('Row '.($i + 2).': '.$e->getMessage());
            }
        }

        return back()->with('success', "Upload completed: {$ok} success, {$fail} failed.");
    }

    public function uploadForm()
    {
        $projectOptions = app(\App\Http\Controllers\JWTController::class)->projectsMap();

        return view('payments.upload', compact('projectOptions'));
    }

    public function view(Request $r)
    {
        $q = PurchasePayment::query();

        if ($r->filled('year')) {
            $q->where('data_year', $r->year);
        } else {
            $q->where('data_year', date('Y'));
        }if ($r->filled('month')) {
            $q->where('data_month', $r->month);
        }if ($r->filled('project_id')) {
            $q->where('project_id', $r->project_id);
        }if ($r->filled('customer')) {
            $q->where('CustomerName', 'like', '%'.$r->customer.'%');
        }if ($r->filled('cluster')) {
            $q->where('Cluster', 'like', '%'.$r->cluster.'%');
        }if ($r->filled('TypePembelian')) {
            $q->where('TypePembelian', $r->TypePembelian);
        }

        $payments = $q->orderBy('PurchaseDate', 'desc')->paginate(50);

        return view('payments.view', [
            'payments' => $payments,
            'filters' => $r->all(),
            'months' => $this->months,
            'projects' => app(\App\Http\Controllers\JWTController::class)->projectsMap(), // <— add
        ]);
    }

    public function export(Request $r)
    {
        $q = PurchasePayment::query();

        $q->when($r->filled('year'), fn ($q) => $q->where('data_year', $r->year), fn ($q) => $q->where('data_year', date('Y')));
        $q->when($r->filled('month'), fn ($q) => $q->where('data_month', $r->month));
        $q->when($r->filled('project_id'), fn ($q) => $q->where('project_id', $r->project_id));
        $q->when($r->filled('customer'), fn ($q) => $q->where('CustomerName', 'like', '%'.$r->customer.'%'));
        $q->when($r->filled('cluster'), fn ($q) => $q->where('Cluster', 'like', '%'.$r->cluster.'%'));
        $q->when($r->filled('TypePembelian'), fn ($q) => $q->where('TypePembelian', $r->TypePembelian));

        $payments = $q->orderBy('PurchaseDate', 'desc')->get();
        $columns = DB::connection('sqlsrv')->getSchemaBuilder()->getColumnListing('purchase_payments');

        $preferredOrder = [
            'No', 'purchaseletter_id', 'Cluster', 'Block', 'Unit', 'CustomerName', 'PurchaseDate', 'LunasDate',
        ];
        $columns = array_values(array_unique(array_merge($preferredOrder, $columns)));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Purchase Payments');
        $sheet->fromArray([$columns], null, 'A1');

        $row = 2;
        foreach ($payments as $index => $payment) {
            $dataRow = [];
            foreach ($columns as $col) {
                if ($col === 'No') {
                    $dataRow[] = $index + 1;
                } else {
                    $val = $payment->{$col} ?? null;
                    if ($val instanceof \Carbon\Carbon) {
                        $val = $val->format('Y-m-d H:i:s');
                    } elseif (preg_match('/_date$/i', $col) && ! empty($val)) {
                        try {
                            $val = \Carbon\Carbon::parse($val)->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                        }
                    }
                    $dataRow[] = $val;
                }
            }
            $sheet->fromArray([$dataRow], null, "A{$row}");
            $row++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $year = $r->year ?? date('Y');
        $month = $r->month ?? date('n');
        $filename = "Purchase_Payments_Full_{$year}_{$month}_".date('YmdHis').'.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = storage_path("app/public/{$filename}");
        $writer->save($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }
}
