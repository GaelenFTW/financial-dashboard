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

        // Assume header row is first row. Map columns by header names present.
        $header = array_shift($rows);
        $this->info('Detected columns: ' . implode(', ', array_values($header)));

        DB::beginTransaction();
        try {
            foreach ($rows as $r) {
                // Adjust these keys to match the column headers in your ODS.
                $custName = trim($r['F'] ?? $r[6] ?? 'Unknown'); // example: CustomerName column
                $cluster  = trim($r['C'] ?? $r[3] ?? null);      // example: Cluster
                $unit     = trim($r['E'] ?? $r[5] ?? null);
                $docNo    = trim($r['B'] ?? $r[2] ?? null);
                $purchaseDate = $r['G'] ?? $r[7] ?? null; // PurchaseDate
                $lunasDate    = $r['H'] ?? $r[8] ?? null; // LunasDate
                $amountRaw = $r['...'] ?? null; // replace with correct column for amount
                $balanceRaw = $r['...'] ?? null; // replace with correct column for balance

                // You must replace '...' keys above with the actual column letter or index from your ODS.
                // For example, if 'Amount' is column 'X', use $r['X'].

                if (empty($custName)) continue;

                $customer = Customer::firstOrCreate(
                    ['name' => $custName],
                    ['cluster' => $cluster, 'unit' => $unit]
                );

                // parse dates (attempt multiple formats)
                $parseDate = function($v){
                    if (!$v) return null;
                    try {
                        return Carbon::parse($v)->toDateString();
                    } catch (\Exception $e) {
                        return null;
                    }
                };

                $invoice = Invoice::create([
                    'doc_no' => $docNo ?: null,
                    'customer_id' => $customer->id,
                    'invoice_date' => $parseDate($purchaseDate),
                    'due_date' => $parseDate($lunasDate),
                    'amount' => is_numeric($amountRaw) ? $amountRaw : 0,
                    'balance' => is_numeric($balanceRaw) ? $balanceRaw : 0,
                    'currency' => 'IDR'
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
