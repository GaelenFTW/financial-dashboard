<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PurchasePaymentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return response()->json(['error' => 'Excel file is empty or invalid'], 400);
        }

        // Extract headers (1st row)
        $headers = $rows[1];
        unset($rows[1]);

        $table = 'purchase_payments';
        $dbColumns = DB::getSchemaBuilder()->getColumnListing($table);
        $year = '2025'; // adjust automatically later if needed

        Log::info("🧾 Excel Headers:", $headers);
        Log::info("🧱 Database Columns for [$table]:", $dbColumns);

        // Build mapping from Excel to DB column names
        $matchedMappings = [];
        $monthList = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];

        // Before January mappings
        $matchedMappings["Amount_Before_Jan_{$year}"] = "Amount_Before_Jan_Year";
        $matchedMappings["Piutang_Before_Jan_{$year}"] = "Piutang_Before_Jan_Year";
        $matchedMappings["Payment_Before_Jan_{$year}"] = "Payment_Before_Jan_Year";

        // Monthly mappings (Jan–Jun)
        foreach ($monthList as $m) {
            $matchedMappings["{$m}_{$year}_DueDate"] = "{$m}_Year_DueDate";
            $matchedMappings["{$m}_{$year}_Type"] = "{$m}_Year_Type";
            $matchedMappings["{$m}_{$year}_Piutang"] = "{$m}_Year_Piutang";
            $matchedMappings["{$m}_{$year}_CairDate"] = "{$m}_Year_CairDate";
            $matchedMappings["{$m}_{$year}_Payment"] = "{$m}_Year_Payment";
        }

        // After June + YTD
        $matchedMappings["Piutang_After_Jun_{$year}"] = "Piutang_After_Year";
        $matchedMappings["Payment_After_Jun_{$year}"] = "Payment_After_Year";
        $matchedMappings["YTD_sd_Jun_{$year}"] = "YTD_sd_Year";
        $matchedMappings["YTD_bayar_Jun_{$year}"] = "YTD_bayar_Year";

        Log::info("✅ Matched Mappings:", $matchedMappings);

        // Identify columns in Excel but not DB, and vice versa
        $excelNames = array_values($headers);
        $excelNotInDb = array_diff($excelNames, array_keys($matchedMappings), $dbColumns);
        $dbNotInExcel = array_diff($dbColumns, array_values($matchedMappings), $excelNames);

        Log::info("❌ Columns in Excel but NOT in DB:", array_values($excelNotInDb));
        Log::info("❌ Columns in DB but NOT in Excel:", array_values($dbNotInExcel));

        // --- Import loop ---
        $imported = 0;
        foreach ($rows as $rowIndex => $row) {
            $data = [];

            foreach ($headers as $colKey => $excelColumn) {
                $value = $row[$colKey] ?? null;
                $dbColumn = $matchedMappings[$excelColumn] ?? $excelColumn;

                if (in_array($dbColumn, $dbColumns)) {
                    $data[$dbColumn] = $value;
                }
            }

            // Fill missing "before June" values with 0
            foreach ($dbColumns as $col) {
                if (preg_match('/(Amount|Piutang|Payment)_(Before|Jan|Feb|Mar|Apr|May|Jun)_Year/', $col)
                    && (!isset($data[$col]) || $data[$col] === null)
                ) {
                    $data[$col] = 0;
                }
            }

            if (empty($data)) {
                Log::warning("⚠️ Row {$rowIndex} produced no valid DB columns.");
                continue;
            }

            try {
                DB::table($table)->insert($data);
                $imported++;
            } catch (\Throwable $e) {
                Log::error("❌ Row {$rowIndex} failed: " . $e->getMessage(), [
                    'data' => $data,
                ]);
            }
        }

        Log::info("✅ Import finished. Imported: {$imported} rows.");

    }

    public function uploadForm() { return view('payments.upload'); }

}
