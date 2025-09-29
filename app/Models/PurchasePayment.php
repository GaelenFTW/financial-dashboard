<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $table = 'purchase_payments';

    protected $fillable = [
        'No', 'purchaseletter_id', 'is_reportcashin', 'Cluster', 'Block', 'Unit',
        'CustomerName', 'PurchaseDate', 'LunasDate', 'is_ppndtp', 'persen_ppndtp',
        'harga_netto', 'TotalPPN', 'harga_bbnsertifikat', 'harga_bajb', 'harga_bphtb',
        'harga_administrasi', 'harga_paket_tambahan', 'harga_admsubsidi', 'biaya_asuransi',
        'HrgJualTotal', 'disc_collection', 'HrgJualTotalminDiscColl', 'TypePembelian',
        'bank_induk', 'KPP', 'JenisKPR', 'Salesman', 'Member', 'tanggal_akad',
        'persen_progress_bangun', 'type_unit',
        'Amount_Before_01_tahun', 'Piutang_Before_01_tahun', 'Payment_Before_01_tahun',

        // Tahun 01 - 07
        '01_tahun_DueDate', '01_tahun_Type', '01_tahun_Piutang', '01_tahun_CairDate', '01_tahun_Payment',
        '02_tahun_DueDate', '02_tahun_Type', '02_tahun_Piutang', '02_tahun_CairDate', '02_tahun_Payment',
        '03_tahun_DueDate', '03_tahun_Type', '03_tahun_Piutang', '03_tahun_CairDate', '03_tahun_Payment',
        '04_tahun_DueDate', '04_tahun_Type', '04_tahun_Piutang', '04_tahun_CairDate', '04_tahun_Payment',
        '05_tahun_DueDate', '05_tahun_Type', '05_tahun_Piutang', '05_tahun_CairDate', '05_tahun_Payment',
        '06_tahun_DueDate', '06_tahun_Type', '06_tahun_Piutang', '06_tahun_CairDate', '06_tahun_Payment',
        '07_tahun_DueDate', '07_tahun_Type', '07_tahun_Piutang', '07_tahun_CairDate', '07_tahun_Payment',

        'Piutang_After_05_tahun', 'Payment_After_05_tahun',
        'YTD_sd_05_tahun', 'YTD_bayar_05_tahun',
        'selisih', 'dari_1_sampai_30_DP', 'dari_31_sampai_60_DP',
        'dari_61_sampai_90_DP', 'diatas_90_DP', 'lebih_bayar',
    ];

    protected $casts = [
        'PurchaseDate'   => 'date',
        'LunasDate'      => 'date',
        'tanggal_akad'   => 'date',

        // Cast tahun dates to Carbon too
        '01_tahun_DueDate' => 'date',
        '01_tahun_CairDate' => 'date',
        '02_tahun_DueDate' => 'date',
        '02_tahun_CairDate' => 'date',
        '03_tahun_DueDate' => 'date',
        '03_tahun_CairDate' => 'date',
        '04_tahun_DueDate' => 'date',
        '04_tahun_CairDate' => 'date',
        '05_tahun_DueDate' => 'date',
        '05_tahun_CairDate' => 'date',
        '06_tahun_DueDate' => 'date',
        '06_tahun_CairDate' => 'date',
        '07_tahun_DueDate' => 'date',
        '07_tahun_CairDate' => 'date',
    ];
}
