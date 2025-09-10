<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('invoices')->insert([
            [
                'doc_no' => '1056',
                'customer' => 'Elite Systems',
                'date' => '2025-06-12',
                'due_date' => '2025-06-27',
                'currency' => 'USD',
                'amount' => 50.00,
                'balance' => 0.00,
            ],
            [
                'doc_no' => '1079',
                'customer' => 'Quantum Services',
                'date' => '2025-06-15',
                'due_date' => '2025-06-30',
                'currency' => 'USD',
                'amount' => 840.00,
                'balance' => 0.00,
            ],
            [
                'doc_no' => '1082',
                'customer' => 'Elite Systems',
                'date' => '2025-06-15',
                'due_date' => '2025-06-30',
                'currency' => 'USD',
                'amount' => 500.00,
                'balance' => 50.00,
            ],
        ]);
    }
}
