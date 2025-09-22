<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    protected $apiUrl;
    protected $jwtController;

    public function __construct(JWTController $jwtController)
    {
        $this->jwtController = $jwtController;
    }

    protected function getData()
    {
        return $this->jwtController->fetchData();
    }

    public function index(Request $request)
{
    $rows = $this->getData();

    if (isset($rows['error'])) {
        return view('dashboard', ['error' => $rows['error']]);
    }

    $rows = array_filter($rows, fn($row) => is_array($row));

    // 🔑 Apply adminid restrictions
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

        // Date range filter
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

    // 📊 Stats
    $totalRevenue = array_sum(array_column($rows, 'HrgJualTotal'));
    $numCustomers = count(array_unique(array_column($rows, 'CustomerName')));
    $productsSold = count($rows);
    $avgRevenue   = $productsSold > 0 ? $totalRevenue / $productsSold : 0;

    $customerRevenue = [];
    foreach ($rows as $row) {
        $name = $row['CustomerName'] ?? 'Unknown';
        $customerRevenue[$name] = ($customerRevenue[$name] ?? 0) + (float)($row['HrgJualTotal'] ?? 0);
    }
    $customers = collect($customerRevenue)->sortDesc()->take(10);

    $productRevenue = [];
    foreach ($rows as $row) {
        $product = $row['type_unit'] ?? 'Unknown';
        $productRevenue[$product] = ($productRevenue[$product] ?? 0) + (float)($row['HrgJualTotal'] ?? 0);
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
