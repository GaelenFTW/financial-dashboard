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

            // Pre-period
            $table->decimal('Amount_Before_Jan_2025', 20, 2)->nullable();
            $table->decimal('Piutang_Before_Jan_2025', 20, 2)->nullable();
            $table->decimal('Payment_Before_Jan_2025', 20, 2)->nullable();

            // Example for Jan–Jun 2025
            $months = ['Jan_2025','Feb_2025','Mar_2025','Apr_2025','May_2025','Jun_2025'];
            foreach ($months as $m) {
                $table->date("{$m}_DueDate")->nullable();
                $table->string("{$m}_Type")->nullable();
                $table->decimal("{$m}_Piutang", 20, 2)->nullable();
                $table->date("{$m}_CairDate")->nullable();
                $table->decimal("{$m}_Payment", 20, 2)->nullable();
            }

            // After June
            $table->decimal('Piutang_After_Jun_2025', 20, 2)->nullable();
            $table->decimal('Payment_After_Jun_2025', 20, 2)->nullable();
            $table->decimal('YTD_sd_Jun_2025', 20, 2)->nullable();
            $table->decimal('YTD_bayar_Jun_2025', 20, 2)->nullable();

            // Other helper columns
            $table->decimal('selisih', 20, 2)->nullable();
            $table->decimal('dari_1_sampai_30_DP', 20, 2)->nullable();
            $table->decimal('dari_31_sampai_60_DP', 20, 2)->nullable();
            $table->decimal('dari_61_sampai_90_DP', 20, 2)->nullable();
            $table->decimal('diatas_90_DP', 20, 2)->nullable();
            $table->decimal('lebih_bayar', 20, 2)->nullable();
            $table->integer('helper_tahun')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_payments');
    }
};
