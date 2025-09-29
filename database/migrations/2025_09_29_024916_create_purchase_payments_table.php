<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('No')->nullable();
            $table->bigInteger('purchaseletter_id')->nullable();
            $table->boolean('is_reportcashin')->nullable();
            $table->string('Cluster')->nullable();
            $table->string('Block')->nullable();
            $table->string('Unit')->nullable();
            $table->string('CustomerName')->nullable();
            $table->date('PurchaseDate')->nullable();
            $table->date('LunasDate')->nullable();
            $table->boolean('is_ppndtp')->nullable();
            $table->decimal('persen_ppndtp', 10, 2)->nullable();
            $table->decimal('harga_netto', 20, 2)->nullable();
            $table->decimal('TotalPPN', 20, 2)->nullable();
            $table->decimal('harga_bbnsertifikat', 20, 2)->nullable();
            $table->decimal('harga_bajb', 20, 2)->nullable();
            $table->decimal('harga_bphtb', 20, 2)->nullable();
            $table->decimal('harga_administrasi', 20, 2)->nullable();
            $table->decimal('harga_paket_tambahan', 20, 2)->nullable();
            $table->decimal('harga_admsubsidi', 20, 2)->nullable();
            $table->decimal('biaya_asuransi', 20, 2)->nullable();
            $table->decimal('HrgJualTotal', 20, 2)->nullable();
            $table->decimal('disc_collection', 20, 2)->nullable();
            $table->decimal('HrgJualTotalminDiscColl', 20, 2)->nullable();
            $table->string('TypePembelian')->nullable();
            $table->string('bank_induk')->nullable();
            $table->string('KPP')->nullable();
            $table->string('JenisKPR')->nullable();
            $table->string('Salesman')->nullable();
            $table->string('Member')->nullable();
            $table->date('tanggal_akad')->nullable();
            $table->decimal('persen_progress_bangun', 10, 2)->nullable();
            $table->string('type_unit')->nullable();

            // Pre & Post tahun fields
            $table->decimal('Amount_Before_01_tahun', 20, 2)->nullable();
            $table->decimal('Piutang_Before_01_tahun', 20, 2)->nullable();
            $table->decimal('Payment_Before_01_tahun', 20, 2)->nullable();

            // Tahun 01 - 07 fields
            for ($i = 1; $i <= 7; $i++) {
                $table->date(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_DueDate')->nullable();
                $table->string(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_Type')->nullable();
                $table->decimal(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_Piutang', 20, 2)->nullable();
                $table->date(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_CairDate')->nullable();
                $table->decimal(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_Payment', 20, 2)->nullable();
            }

            $table->decimal('Piutang_After_05_tahun', 20, 2)->nullable();
            $table->decimal('Payment_After_05_tahun', 20, 2)->nullable();
            $table->decimal('YTD_sd_05_tahun', 20, 2)->nullable();
            $table->decimal('YTD_bayar_05_tahun', 20, 2)->nullable();

            $table->decimal('selisih', 20, 2)->nullable();
            $table->decimal('dari_1_sampai_30_DP', 20, 2)->nullable();
            $table->decimal('dari_31_sampai_60_DP', 20, 2)->nullable();
            $table->decimal('dari_61_sampai_90_DP', 20, 2)->nullable();
            $table->decimal('diatas_90_DP', 20, 2)->nullable();
            $table->decimal('lebih_bayar', 20, 2)->nullable();
            $table->int('helper_tahun')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_payments');
    }
};
