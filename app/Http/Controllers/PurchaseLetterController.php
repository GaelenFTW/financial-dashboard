<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class PurchaseLetterController extends Controller
{
    protected $apiUrl;
    protected $token;

    public function __construct(JWTController $jwtController)
    {
        $this->jwtController = $jwtController;
    }



    
    public function chart()
    {
        $rows  = $this->jwtController->fetchData('api1', ['index.php', 'login.php']);
        $months = [];

        $user = auth()->user();
        if ($user) {
            $userAdminId = (int) $user->AdminID;
            if ($userAdminId !== 0) {
                $rows = array_filter($rows, function ($row) use ($userAdminId) {
                    $rowAdminId = $row['AdminID']
                        ?? $row['adminid']
                        ?? $row['AdminId']
                        ?? null;

                    return (int) $rowAdminId === $userAdminId;
                });
            }
        }

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
        $rows  = $this->jwtController->fetchData('api1', ['index.php', 'login.php']);
        $collection = collect($rows);

        $user = auth()->user();
        if ($user) {
            $userAdminId = (int) $user->AdminID;
            if ($userAdminId !== 0) {
                $collection = $collection->filter(function ($row) use ($userAdminId) {
                    $rowAdminId = $row['AdminID']
                        ?? $row['adminid']
                        ?? $row['AdminId']
                        ?? null;
                    return (int) $rowAdminId === $userAdminId;
                });
            }
        }
        
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