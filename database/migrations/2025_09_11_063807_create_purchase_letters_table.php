<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_letters', function (Blueprint $table) {
            $table->id();
            $table->string('purchaseletter_id')->nullable();
            $table->string('cluster')->nullable();
            $table->string('block')->nullable();
            $table->string('unit')->nullable();
            $table->string('customer_name')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('lunas_date')->nullable();
            $table->boolean('is_ppndtp')->nullable();
            $table->decimal('persen_ppndtp', 8, 2)->nullable();
            $table->decimal('harga_netto', 18, 2)->nullable();
            $table->decimal('total_ppn', 18, 2)->nullable();
            $table->decimal('harga_bbnsertifikat', 18, 2)->nullable();
            $table->decimal('harga_bajb', 18, 2)->nullable();
            $table->decimal('harga_bphtb', 18, 2)->nullable();
            $table->decimal('harga_administrasi', 18, 2)->nullable();
            $table->decimal('harga_paket_tambahan', 18, 2)->nullable();
            $table->decimal('harga_admsubsidi', 18, 2)->nullable();
            $table->decimal('biaya_asuransi', 18, 2)->nullable();
            $table->decimal('hrg_jual_total', 18, 2)->nullable();
            $table->decimal('disc_collection', 18, 2)->nullable();
            $table->decimal('hrg_jual_totalmin_disccoll', 18, 2)->nullable();
            $table->string('type_pembelian')->nullable();
            $table->string('bank_induk')->nullable();
            $table->string('kpp')->nullable();
            $table->string('jenis_kpr')->nullable();
            $table->string('salesman')->nullable();
            $table->string('member')->nullable();
            $table->date('tanggal_akad')->nullable();
            $table->decimal('persen_progress_bangun', 8, 2)->nullable();
            $table->string('type_unit')->nullable();

            // financials
            $table->decimal('amount_before_jan_2024', 18, 2)->nullable();
            $table->decimal('piutang_before_jan_2024', 18, 2)->nullable();
            $table->decimal('payment_before_jan_2024', 18, 2)->nullable();

            // monthly cycles (Jan-Oct)
            foreach ([
                'jan','feb','mar','apr','may','jun','jul','aug','sep','oct'
            ] as $month) {
                $table->date("{$month}_2024_duedate")->nullable();
                $table->string("{$month}_2024_type")->nullable();
                $table->decimal("{$month}_2024_piutang", 18, 2)->nullable();
                $table->date("{$month}_2024_cairdate")->nullable();
                $table->decimal("{$month}_2024_payment", 18, 2)->nullable();
            }

            $table->decimal('piutang_after_oct_2024', 18, 2)->nullable();
            $table->decimal('payment_after_oct_2024', 18, 2)->nullable();
            $table->decimal('ytd_sd_oct_2024', 18, 2)->nullable();
            $table->decimal('ytd_bayar_oct_2024', 18, 2)->nullable();
            $table->decimal('selisih', 18, 2)->nullable();

            // DP buckets
            $table->decimal('dari_1_sampai_30_dp', 18, 2)->nullable();
            $table->decimal('dari_31_sampai_60_dp', 18, 2)->nullable();
            $table->decimal('dari_61_sampai_90_dp', 18, 2)->nullable();
            $table->decimal('diatas_90_dp', 18, 2)->nullable();
            $table->decimal('lebih_bayar', 18, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_letters');
    }
};
