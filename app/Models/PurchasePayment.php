<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    protected $fillable = [
        'Amount_Before_01_tahun','Piutang_Before_01_tahun','Payment_Before_01_tahun',
        'tahun01_DueDate','tahun01_Type','tahun01_Piutang','tahun01_CairDate','tahun01_Payment',
        'tahun02_DueDate','tahun02_Type','tahun02_Piutang','tahun02_CairDate','tahun02_Payment',
        'tahun03_DueDate','tahun03_Type','tahun03_Piutang','tahun03_CairDate','tahun03_Payment',
        'tahun04_DueDate','tahun04_Type','tahun04_Piutang','tahun04_CairDate','tahun04_Payment',
        'tahun05_DueDate','tahun05_Type','tahun05_Piutang','tahun05_CairDate','tahun05_Payment',
        'Piutang_After_05_tahun','Payment_After_05_tahun','YTD_sd_05_tahun','YTD_bayar_05_tahun',
        'tahun06_DueDate','tahun06_Type','tahun06_Piutang','tahun06_CairDate','tahun06_Payment',
        'tahun07_DueDate','tahun07_Type','tahun07_Piutang','tahun07_CairDate','tahun07_Payment',
        'selisih','dari_1_sampai_30_DP','dari_31_sampai_60_DP','dari_61_sampai_90_DP','diatas_90_DP','lebih_bayar'
    ];
}
