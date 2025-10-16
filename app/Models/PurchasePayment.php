<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $table = 'purchase_payments';
    protected $primaryKey = 'No';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [];
    protected $casts = [];

    /**
     * Constructor: dynamically generate fillable & casts just like in controller
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->fillable = $this->generateFillable();
        $this->casts = $this->generateCasts();
    }

    /**
     * Base (non-monthly) columns — permanent
     */
    protected function baseColumns(): array
    {
        return [
            'No', 'purchaseletter_id', 'is_reportcashin', 'Cluster', 'Block', 'Unit',
            'CustomerName', 'PurchaseDate', 'LunasDate', 'is_ppndtp', 'persen_ppndtp',
            'harga_netto', 'TotalPPN', 'harga_bbnsertifikat', 'harga_bajb', 'harga_bphtb',
            'harga_administrasi', 'harga_paket_tambahan', 'harga_admsubsidi', 'biaya_asuransi',
            'HrgJualTotal', 'disc_collection', 'HrgJualTotalminDiscColl', 'TypePembelian',
            'bank_induk', 'KPP', 'JenisKPR', 'Salesman', 'Member', 'tanggal_akad',
            'persen_progress_bangun', 'type_unit', 'selisih', 'dari_1_sampai_30_DP',
            'dari_31_sampai_60_DP', 'dari_61_sampai_90_DP', 'diatas_90_DP', 'lebih_bayar',
            'year', 'month', 'data_year',
        ];
    }

    /**
     * Dynamically generate fillable columns for the given year
     */
    protected function generateFillable(?int $year = null): array
    {
        $year = $year ?: date('Y');
        $fillable = $this->baseColumns();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Before columns
        $fillable[] = "Amount_Before_Jan_{$year}";
        $fillable[] = "Piutang_Before_Jan_{$year}";
        $fillable[] = "Payment_Before_Jan_{$year}";

        // Monthly columns
        foreach ($months as $month) {
            $fillable[] = "{$month}_{$year}_DueDate";
            $fillable[] = "{$month}_{$year}_Type";
            $fillable[] = "{$month}_{$year}_Piutang";
            $fillable[] = "{$month}_{$year}_CairDate";
            $fillable[] = "{$month}_{$year}_Payment";

            // After and YTD columns (month-specific)
            $fillable[] = "Piutang_After_{$month}_{$year}";
            $fillable[] = "Payment_After_{$month}_{$year}";
            $fillable[] = "YTD_sd_{$month}_{$year}";
            $fillable[] = "YTD_bayar_{$month}_{$year}";
        }

        // Generic After/YTD (no month)
        $fillable[] = "Piutang_After_{$year}";
        $fillable[] = "Payment_After_{$year}";
        $fillable[] = "YTD_sd_{$year}";
        $fillable[] = "YTD_bayar_{$year}";

        return $fillable;
    }

    /**
     * Automatically cast all *_DueDate and *_CairDate fields as dates
     */
    protected function generateCasts(?int $year = null): array
    {
        $year = $year ?: date('Y');
        $casts = [
            'PurchaseDate' => 'date',
            'LunasDate' => 'date',
            'tanggal_akad' => 'date',
        ];

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($months as $month) {
            $casts["{$month}_{$year}_DueDate"] = 'date';
            $casts["{$month}_{$year}_CairDate"] = 'date';
        }

        return $casts;
    }
}
