<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class PurchaseLetterController extends Controller
{
    protected function getData()
    {
        // ✅ Load token & API URL from config/services.php (better than hardcoding)
        $token = config('services.data_api.token');
        $url   = config('services.data_api.url');

        try {
            if ($url) {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                    ])
                    ->get($url);

                if ($response->successful()) {
                    return $response->json();
                }
            }
        } catch (\Exception $e) {
            // fail silently
            // \Log::error('PurchaseLetter API error: '.$e->getMessage());
        }

        // ✅ fallback to local file if API fails
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
                    'paid'    => 0,
                    'open'    => 0,
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

    public function index(Request $request)
    {
        $rows = $this->getData();
        $collection = collect($rows);

        // 🔍 Search filter
        $search = $request->get('search');
        if ($search) {
            $collection = $collection->filter(function ($row) use ($search) {
                $search = strtolower($search);

                return str_contains(strtolower($row['CustomerName'] ?? ''), $search) ||
                       str_contains(strtolower($row['Cluster'] ?? ''), $search) ||
                       str_contains(strtolower($row['PurchaseDate'] ?? ''), $search) ||
                       str_contains(strtolower($row['Unit'] ?? ''), $search) ||
                       str_contains(strtolower($row['TypePembelian'] ?? ''), $search);
            });
        }

        // Pagination
        $perPage     = 10;
        $currentPage = $request->get('page', 1);
        $pagedData   = $collection->forPage($currentPage, $perPage);

        $letters = new LengthAwarePaginator(
            $pagedData,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('purchase_letters.index', compact('letters', 'search'));
    }
}
