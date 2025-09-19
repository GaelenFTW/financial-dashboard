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

    public function __construct()
    {
        $this->apiUrl = config('jwt.api_url');
        $this->token = null;
    }

    protected function login()
    {
        try {
            $loginUrl = str_replace('index.php', 'login.php', $this->apiUrl);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($loginUrl, [
                'username' => 'testuser',
                'password' => 'Test123!'
            ]);

            Log::info('Login response status: ' . $response->status());
            Log::info('Login response: ' . $response->body());

            if (!$response->successful()) {
                Log::error('Login failed with status: ' . $response->status());
                return null;
            }

            $data = $response->json();
            if (!isset($data['token'])) {
                Log::error('No token in response');
                return null;
            }

            $this->token = $data['token'];
            Log::info('Login successful');
            return $this->token;

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return null;
        }
    }

    protected function getData()
    {
        try {
            // Try to login if no token exists
            if (!$this->token) {
                $token = $this->login();
                if (!$token) {
                    return ['error' => 'Authentication failed'];
                }
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json'
            ])->get($this->apiUrl);

            if (!$response->successful()) {
                // If token expired, try to login again
                if ($response->status() === 401) {
                    $token = $this->login();
                    if ($token) {
                        // Retry the request with new token
                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept' => 'application/json'
                        ])->get($this->apiUrl);
                    }
                }

                if (!$response->successful()) {
                    return ['error' => 'API request failed: ' . $response->body()];
                }
            }

            $data = $response->json();
            if (empty($data)) {
                return ['error' => 'No data received from API'];
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('getData error: ' . $e->getMessage());
            return ['error' => 'Failed to fetch data: ' . $e->getMessage()];
        }
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