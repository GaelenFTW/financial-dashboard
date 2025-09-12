<?php

namespace App\Http\Controllers;

use App\Models\PurchaseLetter;

class PurchaseLetterController extends Controller
{
    public function index()
    {
        // use PurchaseDate to order since you don't have created_at
        $letters = PurchaseLetter::orderBy('PurchaseDate', 'desc')->paginate(20);

        return view('purchase_letters.index', compact('letters'));
    }
 
        public function chart()
        {
            $data = \DB::table('Worksheet$')
                ->selectRaw("
                    FORMAT(CONVERT(date, PurchaseDate, 105), 'yyyy-MM') as month,
                    SUM(TRY_CAST(REPLACE(REPLACE(REPLACE(HrgJualTotal, 'Rp ', ''), '.', ''), ',', '') AS bigint)) as paid,
                    SUM(CASE WHEN LunasDate IS NULL 
                        THEN TRY_CAST(REPLACE(REPLACE(REPLACE(HrgJualTotal, 'Rp ', ''), '.', ''), ',', '') AS bigint) 
                        ELSE 0 
                    END) as open_amount
                ")
                ->whereRaw("ISDATE(PurchaseDate) = 1") // only valid dates
                ->groupByRaw("FORMAT(CONVERT(date, PurchaseDate, 105), 'yyyy-MM')")
                ->orderByRaw("FORMAT(CONVERT(date, PurchaseDate, 105), 'yyyy-MM')")
                ->get();

            $months = [];
            $paid = [];
            $open = [];
            $overdue = []; // placeholder logic

            foreach ($data as $row) {
                $months[] = $row->month;
                $paid[] = (float) $row->paid;
                $open[] = (float) $row->open_amount;
                $overdue[] = 0; // adjust if you have overdue logic
            }

            return view('purchase_letters.charts', compact('months', 'paid', 'open', 'overdue'));
        }
        
}