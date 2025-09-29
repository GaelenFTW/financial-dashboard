<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->string('No')->nullable();
            $table->string('purchaseletter_id')->nullable();
            $table->boolean('is_reportcashin')->nullable();
            $table->string('Cluster')->nullable();
            $table->string('Block')->nullable();
            $table->string('Unit')->nullable();
            $table->string('CustomerName')->nullable();
            $table->date('PurchaseDate')->nullable();
            $table->date('LunasDate')->nullable();
            $table->boolean('is_ppndtp')->nullable();
            $table->decimal('persen_ppndtp', 10, 2)->nullable();
            $table->decimal('harga_netto', 18, 2)->nullable();
            $table->decimal('TotalPPN', 18, 2)->nullable();
            $table->decimal('harga_bbnsertifikat', 18, 2)->nullable();
            $table->decimal('harga_bajb', 18, 2)->nullable();
            $table->decimal('harga_bphtb', 18, 2)->nullable();
            $table->decimal('harga_administrasi', 18, 2)->nullable();
            $table->decimal('harga_paket_tambahan', 18, 2)->nullable();
            $table->decimal('harga_admsubsidi', 18, 2)->nullable();
            $table->decimal('biaya_asuransi', 18, 2)->nullable();
            $table->decimal('HrgJualTotal', 18, 2)->nullable();
            $table->decimal('disc_collection', 18, 2)->nullable();
            $table->decimal('HrgJualTotalminDiscColl', 18, 2)->nullable();
            $table->string('TypePembelian')->nullable();
            $table->string('bank_induk')->nullable();
            $table->string('KPP')->nullable();
            $table->string('JenisKPR')->nullable();
            $table->string('Salesman')->nullable();
            $table->string('Member')->nullable();
            $table->date('tanggal_akad')->nullable();
            $table->decimal('persen_progress_bangun', 5, 2)->nullable();
            $table->string('type_unit')->nullable();

            // Before tahun
            $table->decimal('Amount_Before_01_tahun', 18, 2)->nullable();
            $table->decimal('Piutang_Before_01_tahun', 18, 2)->nullable();
            $table->decimal('Payment_Before_01_tahun', 18, 2)->nullable();

            // Tahun 01 – 07
            for ($i = 1; $i <= 7; $i++) {
                $table->date(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_DueDate')->nullable();
                $table->string(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_Type')->nullable();
                $table->decimal(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_Piutang', 18, 2)->nullable();
                $table->date(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_CairDate')->nullable();
                $table->decimal(str_pad($i, 2, '0', STR_PAD_LEFT) . '_tahun_Payment', 18, 2)->nullable();
            }

            // After tahun
            $table->decimal('Piutang_After_05_tahun', 18, 2)->nullable();
            $table->decimal('Payment_After_05_tahun', 18, 2)->nullable();
            $table->decimal('YTD_sd_05_tahun', 18, 2)->nullable();
            $table->decimal('YTD_bayar_05_tahun', 18, 2)->nullable();

            // Extra columns
            $table->decimal('selisih', 18, 2)->nullable();
            $table->decimal('dari_1_sampai_30_DP', 18, 2)->nullable();
            $table->decimal('dari_31_sampai_60_DP', 18, 2)->nullable();
            $table->decimal('dari_61_sampai_90_DP', 18, 2)->nullable();
            $table->decimal('diatas_90_DP', 18, 2)->nullable();
            $table->decimal('lebih_bayar', 18, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
