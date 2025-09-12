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
                    FORMAT(TRY_CAST(PurchaseDate AS date), 'yyyy-MM') as month,
                    SUM(CASE WHEN LunasDate IS NOT NULL 
                            THEN CAST(HrgJualTotal AS bigint) ELSE 0 END) as paid,
                    SUM(CASE WHEN LunasDate IS NULL 
                            THEN CAST(HrgJualTotal AS bigint) ELSE 0 END) as open_amount,
                    SUM(CASE WHEN LunasDate IS NULL 
                            AND TRY_CAST(PurchaseDate AS date) < GETDATE()
                            THEN CAST(HrgJualTotal AS bigint) ELSE 0 END) as overdue
                ")
                ->groupByRaw("FORMAT(TRY_CAST(PurchaseDate AS date), 'yyyy-MM')")
                ->orderByRaw("FORMAT(TRY_CAST(PurchaseDate AS date), 'yyyy-MM')")
                ->get();


            $months = [];
            $paid = [];
            $open = [];
            $overdue = [];

            foreach ($data as $row) {
                $months[] = $row->month;
                $paid[] = (float) $row->paid;
                $open[] = (float) $row->open_amount;
                $overdue[] = (float) $row->overdue;
            }

            return view('purchase_letters.charts', compact('months', 'paid', 'open', 'overdue'));
        }

        
}