<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';

    // Allow these fields for mass assignment
    protected $fillable = [
        'doc_no',
        'amount',
        'balance',
        'date',
        'due_date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($invoice) {
            if (empty($invoice->doc_no)) {
                $last = Invoice::max('id') ?? 0;
                $invoice->doc_no = 'INV-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
            }

            if (empty($invoice->customer)) {
                $invoice->customer = 'Default Customer';
            }
        });

    }
}
