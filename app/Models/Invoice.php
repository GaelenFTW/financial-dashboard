<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model {
    protected $fillable = ['doc_no','customer_id','invoice_date','due_date','amount','balance','currency'];
    protected $dates = ['invoice_date','due_date'];
    public function customer(){ return $this->belongsTo(Customer::class); }
    public function payments(){ return $this->hasMany(Payment::class); }
}
