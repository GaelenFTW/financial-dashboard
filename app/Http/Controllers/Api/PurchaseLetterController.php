<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchasePayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PurchaseLetterController extends Controller
{
    public function chart()
    {
        $query = PurchasePayment::query();

        // Apply admin filter
        $user = auth()->user();
        if ($user) {
            $userAdminId = (int) ($user->AdminID ?? 0);
            if ($userAdminId !== 999 && $userAdminId !== 0) {
                $query->where('AdminID', $userAdminId);
            }
        }

        $payments = $query->get();
        $months = [];

        foreach ($payments as $payment) {
            $month = null;
            if ($payment->PurchaseDate) {
                try {
                    $dt = Carbon::parse($payment->PurchaseDate);
                    $month = $dt->format('Y-m');
                } catch (\Exception $e) {
                    continue;
                }
            }

            if ($month) {
                if (! isset($months[$month])) {
                    $months[$month] = [
                        'paid' => 0,
                        'open' => 0,
                        'overdue' => 0,
                    ];
                }

                $amount = (float) ($payment->HrgJualTotal ?? 0);

                if ($payment->LunasDate) {
                    $months[$month]['paid'] += $amount;
                } else {
                    $months[$month]['open'] += $amount;

                    try {
                        $purchaseDate = Carbon::parse($payment->PurchaseDate);
                        if ($purchaseDate->lt(Carbon::now())) {
                            $months[$month]['overdue'] += $amount;
                        }
                    } catch (\Exception $e) {
                        // Skip if date parsing fails
                    }
                }
            }
        }

        ksort($months);
        $labels = array_keys($months);
        $paid = array_column($months, 'paid');
        $open = array_column($months, 'open');
        $overdue = array_column($months, 'overdue');

        return response()->json([
            'months' => $labels,
            'paid' => $paid,
            'open' => $open,
            'overdue' => $overdue,
        ]);
    }

    public function index(Request $request)
    {
        $query = PurchasePayment::query();

        // Apply admin filter
        $user = auth()->user();
        if ($user) {
            $userAdminId = (int) ($user->AdminID ?? 0);
            if ($userAdminId !== 999 && $userAdminId !== 0) {
                $query->where('AdminID', $userAdminId);
            }
        }

        // Search filter
        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('CustomerName', 'like', '%'.$search.'%')
                    ->orWhere('Cluster', 'like', '%'.$search.'%')
                    ->orWhere('Unit', 'like', '%'.$search.'%')
                    ->orWhere('TypePembelian', 'like', '%'.$search.'%')
                    ->orWhere('PurchaseDate', 'like', '%'.$search.'%');
            });
        }

        // Order by PurchaseDate descending
        $query->orderBy('PurchaseDate', 'desc');

        // Paginate
        $perPage = (int) $request->get('per_page', 10);

        $letters = $query->paginate($perPage)->appends([
            'search' => $search,
            'per_page' => $perPage
        ]);

        return response()->json([
            'data' => $letters->items(),
            'current_page' => $letters->currentPage(),
            'last_page' => $letters->lastPage(),
            'per_page' => $letters->perPage(),
            'total' => $letters->total(),
        ]);
    }

    public function show($id)
    {
        $letter = PurchasePayment::where('purchaseletter_id', $id)->firstOrFail();

        return response()->json(['data' => $letter]);
    }

    public function export(Request $request)
    {
        $query = PurchasePayment::query();

        // Apply admin filter
        $user = auth()->user();
        if ($user) {
            $userAdminId = (int) ($user->AdminID ?? 0);
            if ($userAdminId !== 999 && $userAdminId !== 0) {
                $query->where('AdminID', $userAdminId);
            }
        }

        // Search filter
        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('CustomerName', 'like', '%'.$search.'%')
                    ->orWhere('Cluster', 'like', '%'.$search.'%')
                    ->orWhere('Unit', 'like', '%'.$search.'%')
                    ->orWhere('TypePembelian', 'like', '%'.$search.'%')
                    ->orWhere('PurchaseDate', 'like', '%'.$search.'%');
            });
        }

        $payments = $query->get();

        if ($payments->isEmpty()) {
            return redirect()->back()->with('error', 'No data to export');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Get column names dynamically
        $firstRecord = $payments->first();
        $columns = array_keys($firstRecord->getAttributes());

        // Set headers
        foreach ($columns as $i => $heading) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col.'1', $heading);
        }

        // Fill data
        $rowIndex = 2;
        foreach ($payments as $payment) {
            foreach ($columns as $i => $colName) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $value = $payment->$colName ?? '';
                $sheet->setCellValue($col.$rowIndex, $value);
            }
            $rowIndex++;
        }

        $fileName = 'purchase_letters_'.date('Y-m-d_His').'.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'letters_export_');
        $writer->save($tmp);

        return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
    }
}
