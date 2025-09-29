<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('purchase_payments', function (Blueprint $table) {
        $table->id();

        // Store only relevant fields (numeric + dates)
        $table->decimal('Amount_Before_01_tahun', 18, 2)->nullable();
        $table->decimal('Piutang_Before_01_tahun', 18, 2)->nullable();
        $table->decimal('Payment_Before_01_tahun', 18, 2)->nullable();

        $table->date('tahun01_DueDate')->nullable();
        $table->string('tahun01_Type')->nullable();
        $table->decimal('tahun01_Piutang', 18, 2)->nullable();
        $table->date('tahun01_CairDate')->nullable();
        $table->decimal('tahun01_Payment', 18, 2)->nullable();

        $table->date('tahun02_DueDate')->nullable();
        $table->string('tahun02_Type')->nullable();
        $table->decimal('tahun02_Piutang', 18, 2)->nullable();
        $table->date('tahun02_CairDate')->nullable();
        $table->decimal('tahun02_Payment', 18, 2)->nullable();

        $table->date('tahun03_DueDate')->nullable();
        $table->string('tahun03_Type')->nullable();
        $table->decimal('tahun03_Piutang', 18, 2)->nullable();
        $table->date('tahun03_CairDate')->nullable();
        $table->decimal('tahun03_Payment', 18, 2)->nullable();

        $table->date('tahun04_DueDate')->nullable();
        $table->string('tahun04_Type')->nullable();
        $table->decimal('tahun04_Piutang', 18, 2)->nullable();
        $table->date('tahun04_CairDate')->nullable();
        $table->decimal('tahun04_Payment', 18, 2)->nullable();

        $table->date('tahun05_DueDate')->nullable();
        $table->string('tahun05_Type')->nullable();
        $table->decimal('tahun05_Piutang', 18, 2)->nullable();
        $table->date('tahun05_CairDate')->nullable();
        $table->decimal('tahun05_Payment', 18, 2)->nullable();

        $table->decimal('Piutang_After_05_tahun', 18, 2)->nullable();
        $table->decimal('Payment_After_05_tahun', 18, 2)->nullable();
        $table->decimal('YTD_sd_05_tahun', 18, 2)->nullable();
        $table->decimal('YTD_bayar_05_tahun', 18, 2)->nullable();

        $table->date('tahun06_DueDate')->nullable();
        $table->string('tahun06_Type')->nullable();
        $table->decimal('tahun06_Piutang', 18, 2)->nullable();
        $table->date('tahun06_CairDate')->nullable();
        $table->decimal('tahun06_Payment', 18, 2)->nullable();

        $table->date('tahun07_DueDate')->nullable();
        $table->string('tahun07_Type')->nullable();
        $table->decimal('tahun07_Piutang', 18, 2)->nullable();
        $table->date('tahun07_CairDate')->nullable();
        $table->decimal('tahun07_Payment', 18, 2)->nullable();

        $table->decimal('selisih', 18, 2)->nullable();
        $table->decimal('dari_1_sampai_30_DP', 18, 2)->nullable();
        $table->decimal('dari_31_sampai_60_DP', 18, 2)->nullable();
        $table->decimal('dari_61_sampai_90_DP', 18, 2)->nullable();
        $table->decimal('diatas_90_DP', 18, 2)->nullable();
        $table->decimal('lebih_bayar', 18, 2)->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
