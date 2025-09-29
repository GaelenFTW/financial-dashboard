<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchasePayment;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class PurchasePaymentController extends Controller
{

    public function uploadForm()
    {
        return view('payments.upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return back()->with('error', 'Excel file is empty!');
        }

        // ✅ First row = headers
        $headers = array_shift($rows);

        // ✅ Mapping Excel header -> DB column
        $mapping = [
            'Amount_Before_01_tahun'   => 'Amount_Before_01_tahun',
            'Piutang_Before_01_tahun'  => 'Piutang_Before_01_tahun',
            'Payment_Before_01_tahun'  => 'Payment_Before_01_tahun',

            '01_tahun_DueDate' => 'tahun01_DueDate',
            '01_tahun_Type'    => 'tahun01_Type',
            '01_tahun_Piutang' => 'tahun01_Piutang',
            '01_tahun_CairDate'=> 'tahun01_CairDate',
            '01_tahun_Payment' => 'tahun01_Payment',

            '02_tahun_DueDate' => 'tahun02_DueDate',
            '02_tahun_Type'    => 'tahun02_Type',
            '02_tahun_Piutang' => 'tahun02_Piutang',
            '02_tahun_CairDate'=> 'tahun02_CairDate',
            '02_tahun_Payment' => 'tahun02_Payment',

            '03_tahun_DueDate' => 'tahun03_DueDate',
            '03_tahun_Type'    => 'tahun03_Type',
            '03_tahun_Piutang' => 'tahun03_Piutang',
            '03_tahun_CairDate'=> 'tahun03_CairDate',
            '03_tahun_Payment' => 'tahun03_Payment',

            '04_tahun_DueDate' => 'tahun04_DueDate',
            '04_tahun_Type'    => 'tahun04_Type',
            '04_tahun_Piutang' => 'tahun04_Piutang',
            '04_tahun_CairDate'=> 'tahun04_CairDate',
            '04_tahun_Payment' => 'tahun04_Payment',

            '05_tahun_DueDate' => 'tahun05_DueDate',
            '05_tahun_Type'    => 'tahun05_Type',
            '05_tahun_Piutang' => 'tahun05_Piutang',
            '05_tahun_CairDate'=> 'tahun05_CairDate',
            '05_tahun_Payment' => 'tahun05_Payment',

            'Piutang_After_05_tahun' => 'Piutang_After_05_tahun',
            'Payment_After_05_tahun' => 'Payment_After_05_tahun',
            'YTD_sd_05_tahun'        => 'YTD_sd_05_tahun',
            'YTD_bayar_05_tahun'     => 'YTD_bayar_05_tahun',

            '06_tahun_DueDate' => 'tahun06_DueDate',
            '06_tahun_Type'    => 'tahun06_Type',
            '06_tahun_Piutang' => 'tahun06_Piutang',
            '06_tahun_CairDate'=> 'tahun06_CairDate',
            '06_tahun_Payment' => 'tahun06_Payment',

            '07_tahun_DueDate' => 'tahun07_DueDate',
            '07_tahun_Type'    => 'tahun07_Type',
            '07_tahun_Piutang' => 'tahun07_Piutang',
            '07_tahun_CairDate'=> 'tahun07_CairDate',
            '07_tahun_Payment' => 'tahun07_Payment',

            'selisih'               => 'selisih',
            'dari_1_sampai_30_DP'   => 'dari_1_sampai_30_DP',
            'dari_31_sampai_60_DP'  => 'dari_31_sampai_60_DP',
            'dari_61_sampai_90_DP'  => 'dari_61_sampai_90_DP',
            'diatas_90_DP'          => 'diatas_90_DP',
            'lebih_bayar'           => 'lebih_bayar',
        ];

        foreach ($rows as $row) {
            $rowData = [];

            // ✅ Convert row values into "header => value"
            foreach ($headers as $col => $headerName) {
                $rowData[$headerName] = $row[$col] ?? null;
            }

            // ✅ Must have purchaseletter_id
            $purchaseLetterId = $rowData['purchaseletter_id'] ?? null;
            if (empty($purchaseLetterId)) {
                continue;
            }

            $data = [];
            foreach ($mapping as $excelKey => $dbKey) {
                $value = $rowData[$excelKey] ?? null;

                // ✅ Handle dates
                if (str_contains($dbKey, 'Date') && !empty($value)) {
                    try {
                        if (is_numeric($value)) {
                            $date = ExcelDate::excelToDateTimeObject($value);
                            $value = Carbon::instance($date)->format('Y-m-d');
                        } else {
                            $value = Carbon::parse($value)->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        $value = null;
                    }
                }

                $data[$dbKey] = $value;
            }

            // ✅ Save to DB (avoid duplicates)
            PurchasePayment::updateOrCreate(
                ['purchaseletter_id' => $purchaseLetterId],
                $data
            );
        }

        return redirect()->route('payments.view')->with('success', 'Excel imported!');
    }


    public function view()
    {
        $payments = PurchasePayment::latest()->paginate(20);
        return view('payments.view', compact('payments'));
    }


    public function reset()
    {
        PurchasePayment::truncate();
        return back()->with('success', 'Payments table reset!');
    }
}
