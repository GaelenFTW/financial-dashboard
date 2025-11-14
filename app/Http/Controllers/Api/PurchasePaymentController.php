<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchasePayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PurchasePaymentController extends Controller
{
    protected $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    protected function getAllowedProjectIds(): array
    {
        $userId = auth()->id();
        Log::info('getAllowedProjectIds - User ID: ' . ($userId ?? 'NULL'));
        
        if (!$userId) {
            Log::warning('No authenticated user');
            return [];
        }

        $projects = DB::table('user_group_access')
            ->where('user_id', $userId)
            ->pluck('project_id')
            ->map(fn ($p) => (int) $p)
            ->unique()
            ->values()
            ->all();

        Log::info('getAllowedProjectIds - Found project IDs: ' . json_encode($projects));
        return $projects;
    }

    protected function getAllowedProjects(): array
    {
        $allowedIds = $this->getAllowedProjectIds();
        $allProjects= app(\App\Http\Controllers\Api\JWTController::class)->projectsMap();
        Log::info('getAllowedProjects - Allowed IDs: ' . json_encode($allowedIds));
        
        // If user has access to project 999999, return all projects
        if (in_array(999999, $allowedIds, true)) {
            return $allProjects;
        }

        try {
            $allProjects = app(\App\Http\Controllers\JWTController::class)->projectsMap();
            Log::info('getAllowedProjects - All projects from projectsMap: ' . json_encode($allProjects));
            Log::info('getAllowedProjects - All projects count: ' . count($allProjects));
            Log::info('getAllowedProjects - All projects keys: ' . json_encode(array_keys($allProjects)));
        } catch (\Exception $e) {
            Log::error('getAllowedProjects - Error getting projectsMap: ' . $e->getMessage());
            return [];
        }
        
        if (empty($allowedIds)) {
            Log::warning('getAllowedProjects - No allowed IDs, returning empty');
            return [];
        }
        
        $filtered = array_filter($allProjects, function($key) use ($allowedIds) {
            $intKey = (int)$key;
            $isAllowed = in_array($intKey, $allowedIds, true);
            Log::info("getAllowedProjects - Checking key {$key} (int: {$intKey}), allowed: " . ($isAllowed ? 'YES' : 'NO'));
            return $isAllowed;
        }, ARRAY_FILTER_USE_KEY);
        
        Log::info('getAllowedProjects - Filtered projects: ' . json_encode($filtered));
        Log::info('getAllowedProjects - Filtered count: ' . count($filtered));
        
        return $filtered;
    }

    protected function applyProjectFilter($query)
    {
        $allowedIds = $this->getAllowedProjectIds();
        
        if (empty($allowedIds)) {
            return $query->whereRaw('1 = 0');
        }

                // If user has access to project 999999, no filter needed
        if (!in_array(999999, $allowedIds, true)) {
            $query->whereIn('project_id', $allowedIds);
        }

        return $query;
    }

    protected function canAccessProject(int $projectId): bool
    {
        $allowedIds = $this->getAllowedProjectIds();
        return in_array(999999, $allowedIds, true) || in_array($projectId, $allowedIds, true);
    }

    public function uploadForm()
    {
        Log::info('=== uploadForm called ===');
        $projectOptions = $this->getAllowedProjects();
        
        Log::info('uploadForm - Final project options: ' . json_encode($projectOptions));
        Log::info('uploadForm - Options count: ' . count($projectOptions));

        return response()->json([
            'view' => 'payments.upload', 
            'projectOptions' => $projectOptions,
            'debug' => [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email ?? 'N/A',
                'allowed_ids' => $this->getAllowedProjectIds(),
                'options_count' => count($projectOptions),
            ]
        ]);
    }

    public function view(Request $r)
    {
        Log::info('=== view called ===');
        $q = PurchasePayment::query();

        $q = $this->applyProjectFilter($q);

        if ($r->filled('year')) {
            $q->where('data_year', $r->year);
        } else {
            $q->where('data_year', date('Y'));
        }

        if ($r->filled('month')) $q->where('data_month', $r->month);
        if ($r->filled('project_id')) $q->where('project_id', $r->project_id);
        if ($r->filled('customer')) $q->where('CustomerName', 'like', '%'.$r->customer.'%');
        if ($r->filled('cluster')) $q->where('Cluster', 'like', '%'.$r->cluster.'%');
        if ($r->filled('TypePembelian')) $q->where('TypePembelian', $r->TypePembelian);

        $payments = $q->orderBy('PurchaseDate', 'desc')->paginate(50);
        $projects = $this->getAllowedProjects();

        Log::info('view - Projects returned: ' . json_encode($projects));

        return response()->json([
            'payments' => $payments,
            'filters' => $r->all(),
            'months' => $this->months,
            'projects' => $projects,
            'debug' => [
                'user_id' => auth()->id(),
                'allowed_ids' => $this->getAllowedProjectIds(),
                'projects_count' => count($projects),
            ]
        ]);
    }

    public function export(Request $r)
    {
        $q = PurchasePayment::query();

        $q = $this->applyProjectFilter($q);

        $q->when($r->filled('year'), fn ($q) => $q->where('data_year', $r->year), fn ($q) => $q->where('data_year', date('Y')));
        $q->when($r->filled('month'), fn ($q) => $q->where('data_month', $r->month));
        $q->when($r->filled('project_id'), fn ($q) => $q->where('project_id', $r->project_id));
        $q->when($r->filled('customer'), fn ($q) => $q->where('CustomerName', 'like', '%'.$r->customer.'%'));
        $q->when($r->filled('cluster'), fn ($q) => $q->where('Cluster', 'like', '%'.$r->cluster.'%'));
        $q->when($r->filled('TypePembelian'), fn ($q) => $q->where('TypePembelian', $r->TypePembelian));

        $payments = $q->orderBy('PurchaseDate', 'desc')->get();
        $columns = DB::connection('sqlsrv')->getSchemaBuilder()->getColumnListing('purchase_payments');

        $preferredOrder = [
            'No', 'purchaseletter_id', 'Cluster', 'Block', 'Unit', 'CustomerName', 'PurchaseDate', 'LunasDate',
        ];
        $columns = array_values(array_unique(array_merge($preferredOrder, $columns)));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Purchase Payments');
        $sheet->fromArray([$columns], null, 'A1');

        $row = 2;
        foreach ($payments as $index => $payment) {
            $dataRow = [];
            foreach ($columns as $col) {
                if ($col === 'No') {
                    $dataRow[] = $index + 1;
                } else {
                    $val = $payment->{$col} ?? null;
                    if ($val instanceof \Carbon\Carbon) {
                        $val = $val->format('Y-m-d H:i:s');
                    } elseif (preg_match('/_date$/i', $col) && ! empty($val)) {
                        try { $val = Carbon::parse($val)->format('Y-m-d H:i:s'); } catch (\Exception $e) {}
                    }
                    $dataRow[] = $val;
                }
            }
            $sheet->fromArray([$dataRow], null, "A{$row}");
            $row++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $year = $r->year ?? date('Y');
        $month = $r->month ?? date('n');
        $filename = "Purchase_Payments_Full_{$year}_{$month}_".date('YmdHis').'.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = storage_path("app/public/{$filename}");
        $writer->save($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }
}
