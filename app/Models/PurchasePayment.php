<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
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
        'Piutang_After_05_tahun', 'Payment_After_05_tahun', 'YTD_sd_05_tahun', 'YTD_bayar_05_tahun',
        'selisih', 'dari_1_sampai_30_DP', 'dari_31_sampai_60_DP', 'dari_61_sampai_90_DP',
        'diatas_90_DP', 'lebih_bayar',
    ];

    // Dynamically add tahun fields (01-07)
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        for ($i = 1; $i <= 7; $i++) {
            $this->fillable[] = str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_DueDate';
            $this->fillable[] = str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_Type';
            $this->fillable[] = str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_Piutang';
            $this->fillable[] = str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_CairDate';
            $this->fillable[] = str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_Payment';
        }
    }
}
