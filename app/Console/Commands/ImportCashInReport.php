<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use DB;

class ImportCashInReport extends Command
{
    protected $signature = 'import:cashin {file=Cash In Payment Report_1732091816.ods}';
    protected $description = 'Import cash in payment report ODS to DB';

    public function handle()
    {
        $file = storage_path('app/' . $this->argument('file'));
        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        $reader = IOFactory::createReaderForFile($file);
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, true);

        // first row = headers
        $header = array_shift($rows);
        $header = array_map('trim', $header); // clean spaces
        $this->info('Detected columns: ' . implode(', ', $header));

        DB::beginTransaction();
        try {
            foreach ($rows as $r) {
                // remap row to use header names as keys
                $row = [];
                foreach ($header as $colIndex => $colName) {
                    if ($colName) {
                        $row[$colName] = $r[$colIndex] ?? null;
                    }
                }

                $custName     = trim($row['CustomerName'] ?? '');
                $docNo        = trim($row['purchaseletter_id'] ?? null);
                $cluster      = trim($row['Cluster'] ?? null);
                $block        = trim($row['Block'] ?? null);
                $unit         = trim($row['Unit'] ?? null);
                $purchaseDate = $row['G'] ?? null;
                $lunasDate    = $row['H'] ?? null;
                $amountRaw    = $row['HrgJualTotal'] ?? null;
                $balanceRaw   = $row['selisih'] ?? null;

                if (empty($custName)) continue;

                $customer = Customer::firstOrCreate(
                    ['name' => $custName],
                    ['cluster' => $cluster, 'unit' => $unit]
                );

                $parseDate = function($v) {
                    if (!$v) return null;
                    try {
                        return Carbon::parse($v)->toDateString();
                    } catch (\Exception $e) {
                        return null;
                    }
                };

                Invoice::create([
                    'doc_no'        => $docNo,
                    'customer_id'   => $customer->id,
                    'purchase_date' => $parseDate($purchaseDate),
                    'lunas_date'    => $parseDate($lunasDate),
                    'amount'        => is_numeric($amountRaw) ? $amountRaw : 0,
                    'balance'       => is_numeric($balanceRaw) ? $balanceRaw : 0,
                    'currency'      => 'IDR',
                ]);
            }

            DB::commit();
            $this->info('Import completed');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return 1;
        }
        return 0;
    }
}
