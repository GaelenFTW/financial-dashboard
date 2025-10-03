<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $table = 'purchase_payments';
    protected $primaryKey = 'No'; // or purchaseletter_id, whichever is correct
    public $incrementing = false;
    public $timestamps = false;


    protected $fillable = [
        'No', 'purchaseletter_id', 'is_reportcashin', 'Cluster', 'Block', 'Unit',
        'CustomerName', 'PurchaseDate', 'LunasDate', 'is_ppndtp', 'persen_ppndtp',
        'harga_netto', 'TotalPPN', 'harga_bbnsertifikat', 'harga_bajb', 'harga_bphtb',
        'harga_administrasi', 'harga_paket_tambahan', 'harga_admsubsidi', 'biaya_asuransi',
        'HrgJualTotal', 'disc_collection', 'HrgJualTotalminDiscColl', 'TypePembelian',
        'bank_induk', 'KPP', 'JenisKPR', 'Salesman', 'Member', 'tanggal_akad',
        'persen_progress_bangun', 'type_unit',

        'Amount_Before_Jan_2025', 'Piutang_Before_Jan_2025', 'Payment_Before_Jan_2025',

        // Monthly columns Jan – Jun 2025
        'Jan_2025_DueDate', 'Jan_2025_Type', 'Jan_2025_Piutang', 'Jan_2025_CairDate', 'Jan_2025_Payment',
        'Feb_2025_DueDate', 'Feb_2025_Type', 'Feb_2025_Piutang', 'Feb_2025_CairDate', 'Feb_2025_Payment',
        'Mar_2025_DueDate', 'Mar_2025_Type', 'Mar_2025_Piutang', 'Mar_2025_CairDate', 'Mar_2025_Payment',
        'Apr_2025_DueDate', 'Apr_2025_Type', 'Apr_2025_Piutang', 'Apr_2025_CairDate', 'Apr_2025_Payment',
        'May_2025_DueDate', 'May_2025_Type', 'May_2025_Piutang', 'May_2025_CairDate', 'May_2025_Payment',
        'Jun_2025_DueDate', 'Jun_2025_Type', 'Jun_2025_Piutang', 'Jun_2025_CairDate', 'Jun_2025_Payment',

        // After June summary
        'Piutang_After_Jun_2025', 'Payment_After_Jun_2025',
        'YTD_sd_Jun_2025', 'YTD_bayar_Jun_2025',

        'selisih', 'dari_1_sampai_30_DP', 'dari_31_sampai_60_DP',
        'dari_61_sampai_90_DP', 'diatas_90_DP', 'lebih_bayar',
        'year', 'month','data_year'
    ];

    protected $casts = [
        'PurchaseDate'   => 'date',
        'LunasDate'      => 'date',
        'tanggal_akad'   => 'date',

        // Cast monthly due dates + cair dates
        'Jan_2025_DueDate' => 'date',
        'Jan_2025_CairDate' => 'date',
        'Feb_2025_DueDate' => 'date',
        'Feb_2025_CairDate' => 'date',
        'Mar_2025_DueDate' => 'date',
        'Mar_2025_CairDate' => 'date',
        'Apr_2025_DueDate' => 'date',
        'Apr_2025_CairDate' => 'date',
        'May_2025_DueDate' => 'date',
        'May_2025_CairDate' => 'date',
        'Jun_2025_DueDate' => 'date',
        'Jun_2025_CairDate' => 'date',
    ];
}
