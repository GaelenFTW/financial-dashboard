<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    protected function getData()
    {

        $token = env('DATA_API_TOKEN');
        $url = config('services.data_api.url');

        try {
            if ($url) {
                // ✅ Attach JWT token to the request
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $token
                    ])
                    ->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    // If the API response is wrapped in a key, unwrap it
                    if (isset($data['data']) && is_array($data['data'])) {
                        return $data['data'];
                    }

                    // If it's already a flat array
                    if (is_array($data)) {
                        return $data;
                    }
                }
            }
        } catch (\Exception $e) {
            // fail silently and fallback
        }

        // fallback: local file
        $path = public_path('data.json');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);

            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            }

            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    public function index(Request $request)
    {
        $rows = $this->getData();
        $rows = array_filter($rows, fn($row) => is_array($row));

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

            // start-end date range
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

        // Aggregates
        $totalRevenue = array_sum(array_column($rows, 'HrgJualTotal'));
        $numCustomers = count(array_unique(array_column($rows, 'CustomerName')));
        $productsSold = count($rows);
        $avgRevenue   = $productsSold > 0 ? $totalRevenue / $productsSold : 0;

        // Top 10 customers
        $customerRevenue = [];
        foreach ($rows as $row) {
            $name = $row['CustomerName'] ?? 'Unknown';
            $customerRevenue[$name] =
                ($customerRevenue[$name] ?? 0) + (float)($row['HrgJualTotal'] ?? 0);
        }
        $customers = collect($customerRevenue)->sortDesc()->take(10);

        // Top 10 products
        $productRevenue = [];
        foreach ($rows as $row) {
            $product = $row['type_unit'] ?? 'Unknown';
            $productRevenue[$product] =
                ($productRevenue[$product] ?? 0) + (float)($row['HrgJualTotal'] ?? 0);
        }
        $products = collect($productRevenue)->sortDesc()->take(10);

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
