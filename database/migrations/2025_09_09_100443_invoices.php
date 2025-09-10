<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('doc_no');
            $table->string('customer');
            $table->date('date');
            $table->date('due_date');
            $table->string('currency')->default('USD');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('invoices');
    }
};
