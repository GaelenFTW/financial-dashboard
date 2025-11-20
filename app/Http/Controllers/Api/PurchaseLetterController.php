<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchasePayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class PurchaseLetterController extends BaseController
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
    use \Illuminate\Foundation\Validation\ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    protected function getFilteredQuery()
    {
        try {
            $user = auth('sanctum')->user();
            
            if (!$user) {
                Log::error('PurchaseLetterController: No authenticated user found');
                abort(401, 'Unauthorized');
            }

            Log::info('PurchaseLetterController: User authenticated', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'group_id' => $user->group_id
            ]);

            $query = PurchasePayment::query();

            // Get user's allowed project IDs
            $allowedProjectIds = $user->getAllowedProjectIds();
            
            Log::info('PurchaseLetterController: User project access', [
                'user_id' => $user->id,
                'allowed_projects' => $allowedProjectIds,
                'request_project_id' => request('project_id')
            ]);

            if (empty($allowedProjectIds)) {
                // No access to any project
                Log::warning('PurchaseLetterController: User has no project access', [
                    'user_id' => $user->id
                ]);
                return $query->whereRaw('1 = 0');
            }

            // Check if user has "all projects" access
            if (in_array(999999, $allowedProjectIds, true)) {
                Log::info('PurchaseLetterController: User has all projects access');
                
                // User can see all projects, but let them filter by specific project
                if (request()->filled('project_id')) {
                    $query->where('project_id', request('project_id'));
                    Log::info('PurchaseLetterController: Filtering by project', [
                        'project_id' => request('project_id')
                    ]);
                }
                // Otherwise, show all projects
            } else {
                Log::info('PurchaseLetterController: User has limited project access');
                
                // User has access to specific projects only
                if (request()->filled('project_id')) {
                    // Verify the requested project is in their allowed list
                    if (in_array((int)request('project_id'), $allowedProjectIds, true)) {
                        $query->where('project_id', request('project_id'));
                        Log::info('PurchaseLetterController: Filtering by allowed project', [
                            'project_id' => request('project_id')
                        ]);
                    } else {
                        // Requested project not allowed
                        Log::warning('PurchaseLetterController: User requested unauthorized project', [
                            'user_id' => $user->id,
                            'requested_project' => request('project_id'),
                            'allowed_projects' => $allowedProjectIds
                        ]);
                        return $query->whereRaw('1 = 0');
                    }
                } else {
                    // Show all their allowed projects
                    $query->whereIn('project_id', $allowedProjectIds);
                    Log::info('PurchaseLetterController: Showing all allowed projects', [
                        'project_ids' => $allowedProjectIds
                    ]);
                }
            }

            return $query;
            
        } catch (\Exception $e) {
            Log::error('PurchaseLetterController: Error in getFilteredQuery', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function chart()
    {
        try {
            $query = $this->getFilteredQuery();
            $payments = $query->get();
            $months = [];

            foreach ($payments as $payment) {
                $month = null;
                if ($payment->PurchaseDate) {
                    try {
                        $dt = Carbon::parse($payment->PurchaseDate);
                        $month = $dt->format('Y-m');
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if ($month) {
                    if (! isset($months[$month])) {
                        $months[$month] = [
                            'paid' => 0,
                            'open' => 0,
                            'overdue' => 0,
                        ];
                    }

                    $amount = (float) ($payment->HrgJualTotal ?? 0);

                    if ($payment->LunasDate) {
                        $months[$month]['paid'] += $amount;
                    } else {
                        $months[$month]['open'] += $amount;

                        try {
                            $purchaseDate = Carbon::parse($payment->PurchaseDate);
                            if ($purchaseDate->lt(Carbon::now())) {
                                $months[$month]['overdue'] += $amount;
                            }
                        } catch (\Exception $e) {
                            // Skip
                        }
                    }
                }
            }

            ksort($months);
            $labels = array_keys($months);
            $paid = array_column($months, 'paid');
            $open = array_column($months, 'open');
            $overdue = array_column($months, 'overdue');

            return response()->json([
                'months' => $labels,
                'paid' => $paid,
                'open' => $open,
                'overdue' => $overdue,
            ]);
        } catch (\Exception $e) {
            Log::error('PurchaseLetterController: Chart error', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Failed to load chart data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            Log::info('PurchaseLetterController: Index method called', [
                'params' => $request->all()
            ]);

            $query = $this->getFilteredQuery();

            // Search filter
            $search = $request->get('search');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('CustomerName', 'like', '%'.$search.'%')
                        ->orWhere('Cluster', 'like', '%'.$search.'%')
                        ->orWhere('Unit', 'like', '%'.$search.'%')
                        ->orWhere('TypePembelian', 'like', '%'.$search.'%')
                        ->orWhere('PurchaseDate', 'like', '%'.$search.'%');
                });
            }

            // Sorting
            $sortField = $request->get('sort_field', 'PurchaseDate');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortField, $sortOrder);

            // Get total count
            $totalCount = $query->count();
            Log::info('PurchaseLetterController: Total records found', [
                'count' => $totalCount
            ]);

            // Paginate
            $perPage = (int) $request->get('per_page', 50);
            $letters = $query->paginate($perPage)->appends([
                'search' => $search,
                'per_page' => $perPage,
                'project_id' => request('project_id'),
                'sort_field' => $sortField,
                'sort_order' => $sortOrder
            ]);

            Log::info('PurchaseLetterController: Returning paginated data', [
                'items_count' => count($letters->items())
            ]);

            return response()->json([
                'data' => $letters->items(),
                'current_page' => $letters->currentPage(),
                'last_page' => $letters->lastPage(),
                'per_page' => $letters->perPage(),
                'total' => $letters->total(),
            ]);
        } catch (\Exception $e) {
            Log::error('PurchaseLetterController: Index error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'error' => 'Failed to load data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $query = $this->getFilteredQuery();
            $letter = $query->where('purchaseletter_id', $id)->firstOrFail();

            return response()->json(['data' => $letter]);
        } catch (\Exception $e) {
            Log::error('PurchaseLetterController: Show error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Record not found'], 404);
        }
    }

    public function export(Request $request)
    {
        try {
            $query = $this->getFilteredQuery();

            $search = $request->get('search');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('CustomerName', 'like', '%'.$search.'%')
                        ->orWhere('Cluster', 'like', '%'.$search.'%')
                        ->orWhere('Unit', 'like', '%'.$search.'%')
                        ->orWhere('TypePembelian', 'like', '%'.$search.'%')
                        ->orWhere('PurchaseDate', 'like', '%'.$search.'%');
                });
            }

            $payments = $query->get();

            if ($payments->isEmpty()) {
                return response()->json(['error' => 'No data to export'], 400);
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            $firstRecord = $payments->first();
            $columns = array_keys($firstRecord->getAttributes());

            foreach ($columns as $i => $heading) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue($col.'1', $heading);
            }

            $rowIndex = 2;
            foreach ($payments as $payment) {
                foreach ($columns as $i => $colName) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    $value = $payment->$colName ?? '';
                    $sheet->setCellValue($col.$rowIndex, $value);
                }
                $rowIndex++;
            }

            $fileName = 'purchase_letters_'.date('Y-m-d_His').'.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tmp = tempnam(sys_get_temp_dir(), 'letters_export_');
            $writer->save($tmp);

            return response()->download($tmp, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('PurchaseLetterController: Export error', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    public function getAvailableProjects()
    {
        try {
            $user = auth('sanctum')->user();
            $allowedProjectIds = $user->getAllowedProjectIds();

            if (empty($allowedProjectIds)) {
                return response()->json(['projects' => []]);
            }

            $query = \DB::table('master_projects')
                ->select('project_id', 'project_name')
                ->orderBy('project_name');

            if (!in_array(999999, $allowedProjectIds, true)) {
                $query->whereIn('project_id', $allowedProjectIds);
            }

            $projects = $query->get();

            return response()->json(['projects' => $projects]);
        } catch (\Exception $e) {
            Log::error('PurchaseLetterController: Get projects error', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['projects' => []], 500);
        }
    }
}
