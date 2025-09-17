<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class PurchaseLetterController extends Controller
{
    protected function getData()
    {
        $url = config('services.data_api.url');

        try {
            if ($url) {
                $response = Http::timeout(10)->get($url);
                if ($response->successful()) {
                    return $response->json();
                }
            }
        } catch (\Exception $e) {
            // fail silently and fallback
        }

        // fallback to local file
        $path = public_path('data.json');
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return [];
    }

    public function chart()
    {
        $rows = $this->getData();

        $months = [];

        foreach ($rows as $row) {
            $month = null;
            if (!empty($row['PurchaseDate'])) {
                $dt = \DateTime::createFromFormat('d-m-Y', $row['PurchaseDate']);
                if ($dt) {
                    $month = $dt->format('Y-m'); // normalize to YYYY-MM
                }
            }

            if ($month) {
                $months[$month] = $months[$month] ?? [
                    'paid' => 0,
                    'open' => 0,
                    'overdue' => 0,
                ];

                $amount = (float) ($row['HrgJualTotal'] ?? 0);

                if (!empty($row['LunasDate'])) {
                    $months[$month]['paid'] += $amount;
                } else {
                    $months[$month]['open'] += $amount;
                    if ($dt && $dt < new \DateTime()) {
                        $months[$month]['overdue'] += $amount;
                    }
                }
            }
        }

        // ✅ Sort months chronologically
        ksort($months);

        $labels   = array_keys($months);
        $paid     = array_column($months, 'paid');
        $open     = array_column($months, 'open');
        $overdue  = array_column($months, 'overdue');

        return view('purchase_letters.charts', [
            'months'   => $labels,
            'paid'     => $paid,
            'open'     => $open,
            'overdue'  => $overdue,
        ]);
    }

public function index()
{
    $rows = $this->getData();
    $collection = collect($rows);

    // 🔍 Search filter
    $search = request()->get('search');
    if ($search) {
        $collection = $collection->filter(function ($row) use ($search) {
            return str_contains(strtolower($row['CustomerName']), strtolower($search)) ||
                   str_contains(strtolower($row['Cluster']), strtolower($search)) ||
                   str_contains(strtolower($row['PurchaseDate']), strtolower($search)) ||
                   str_contains(strtolower($row['Unit']), strtolower($search));
                   
        });
    }

    // Pagination
    $perPage = 10;
    $currentPage = request()->get('page', 1);
    $pagedData = $collection->forPage($currentPage, $perPage);

    $letters = new LengthAwarePaginator(
        $pagedData,
        $collection->count(),
        $perPage,
        $currentPage,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('purchase_letters.index', compact('letters', 'search'));
}

}
