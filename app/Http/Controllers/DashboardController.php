<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class DashboardController extends Controller
{

    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('jwt.api_url');
    }

    protected function login()
    {
        try {
            $response = Http::post($this->apiUrl . '/login', [
                'username' => 'testuser',
                'password' => 'Test123!'
            ]);

            if (!$response->successful()) {
                Log::error('Login failed: ' . $response->body());
                return null;
            }

            $data = $response->json();
            if (isset($data['token'])) {
                session(['api_token' => $data['token']]);
                return $data['token'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return null;
        }
    }

    protected function getData()
{
    // dd(session('api_token'));
    try {
        if (!session('api_token')) {
            $token = $this->login();
            if (!$token) {
                return ['error' => 'Authentication failed, no token'];
            }
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . session('api_token'),
            'Accept' => 'application/json'
        ])->get($this->apiUrl);

        if (!$response->successful()) {
            if ($response->status() === 401) {
                $token = $this->login();
                if (!$token) {
                    return ['error' => 'Authentication failed after retry'];
                }
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ])->get($this->apiUrl);
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


    public function index(Request $request)
    {
        $rows = $this->getData();
        
        if (isset($rows['error'])) {
            return view('dashboard', ['error' => $rows['error']]);
        }

        $rows = array_filter($rows, fn($row) => is_array($row));

        // Apply filters
        $rows = array_filter($rows, function ($row) use ($request) {
            if ($request->filled('cluster') && strcasecmp($row['Cluster'] ?? '', $request->cluster) !== 0) {
                return false;
            }

            if ($request->filled('typepembelian')) {
                $filterValue = strtolower(trim($request->typepembelian));
                $rowValue    = strtolower(trim($row['TypePembelian'] ?? ''));
                if (strpos($rowValue, $filterValue) === false) {
                    return false;
                }
            }

            if ($request->filled('customername') && stripos($row['CustomerName'] ?? '', $request->customername) === false) {
                return false;
            }

            if ($request->filled('type_unit') && strcasecmp($row['type_unit'] ?? '', $request->type_unit) !== 0) {
                return false;
            }

            // Date range filtering
            if ($request->filled('startdate') || $request->filled('enddate')) {
                $purchaseDate = isset($row['PurchaseDate']) ? strtotime(str_replace('-', '/', $row['PurchaseDate'])) : null;

                if ($request->filled('startdate')) {
                    $startDate = strtotime($request->startdate);
                    if ($purchaseDate < $startDate) {
                        return false;
                    }
                }

                if ($request->filled('enddate')) {
                    $endDate = strtotime($request->enddate);
                    if ($purchaseDate > $endDate) {
                        return false;
                    }
                }
            }

            return true;
        });

        // Calculate aggregates
        $totalRevenue = array_sum(array_column($rows, 'HrgJualTotal'));
        $numCustomers = count(array_unique(array_column($rows, 'CustomerName')));
        $productsSold = count($rows);
        $avgRevenue   = $productsSold > 0 ? $totalRevenue / $productsSold : 0;

        // Calculate top 10 customers by revenue
        $customerRevenue = [];
        foreach ($rows as $row) {
            $name = $row['CustomerName'] ?? 'Unknown';
            $customerRevenue[$name] = ($customerRevenue[$name] ?? 0) + (float)($row['HrgJualTotal'] ?? 0);
        }
        $customers = collect($customerRevenue)->sortDesc()->take(10);

        // Calculate top 10 products by revenue
        $productRevenue = [];
        foreach ($rows as $row) {
            $product = $row['type_unit'] ?? 'Unknown';
            $productRevenue[$product] = ($productRevenue[$product] ?? 0) + (float)($row['HrgJualTotal'] ?? 0);
        }
        $products = collect($productRevenue)->sortDesc()->take(10);

        // Return view with all calculated data
        return view('dashboard', [
            'customers'    => $customers,
            'products'     => $products,
            'totalRevenue' => $totalRevenue,
            'numCustomers' => $numCustomers,
            'productsSold' => $productsSold,
            'avgRevenue'   => $avgRevenue,
            'filters'      => $request->all(),
        ]);
    }
}