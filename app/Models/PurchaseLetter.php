<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseLetter extends Model
{

    protected $table = 'Cash_In_Payment_Report_1732091816';
    public $timestamps = false; // ⬅️ disable created_at / updated_at

    protected $fillable = [
        'purchaseletter_id','cluster','block','unit','customer_name',
        'purchase_date','lunas_date','is_ppndtp','persen_ppndtp',
        'harga_netto','total_ppn','harga_bbnsertifikat','harga_bajb',
        'harga_bphtb','harga_administrasi','harga_paket_tambahan',
        'harga_admsubsidi','biaya_asuransi','hrg_jual_total',
        'disc_collection','hrg_jual_totalmin_disccoll','type_pembelian',
        'bank_induk','kpp','jenis_kpr','salesman','member','tanggal_akad',
        'persen_progress_bangun','type_unit',
        'amount_before_jan_2024','piutang_before_jan_2024','payment_before_jan_2024',
        'jan_2024_duedate','jan_2024_type','jan_2024_piutang','jan_2024_cairdate','jan_2024_payment',
        'feb_2024_duedate','feb_2024_type','feb_2024_piutang','feb_2024_cairdate','feb_2024_payment',
        'mar_2024_duedate','mar_2024_type','mar_2024_piutang','mar_2024_cairdate','mar_2024_payment',
        'apr_2024_duedate','apr_2024_type','apr_2024_piutang','apr_2024_cairdate','apr_2024_payment',
        'may_2024_duedate','may_2024_type','may_2024_piutang','may_2024_cairdate','may_2024_payment',
        'jun_2024_duedate','jun_2024_type','jun_2024_piutang','jun_2024_cairdate','jun_2024_payment',
        'jul_2024_duedate','jul_2024_type','jul_2024_piutang','jul_2024_cairdate','jul_2024_payment',
        'aug_2024_duedate','aug_2024_type','aug_2024_piutang','aug_2024_cairdate','aug_2024_payment',
        'sep_2024_duedate','sep_2024_type','sep_2024_piutang','sep_2024_cairdate','sep_2024_payment',
        'oct_2024_duedate','oct_2024_type','oct_2024_piutang','oct_2024_cairdate','oct_2024_payment',
        'piutang_after_oct_2024','payment_after_oct_2024','ytd_sd_oct_2024','ytd_bayar_oct_2024',
        'selisih','dari_1_sampai_30_dp','dari_31_sampai_60_dp','dari_61_sampai_90_dp','diatas_90_dp','lebih_bayar'
    ];
}
