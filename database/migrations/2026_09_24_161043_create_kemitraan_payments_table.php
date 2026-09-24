<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kemitraan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('kemitraan_invoices')->onDelete('cascade');
            $table->decimal('nominal_bayar', 15, 2)->default(0);
            $table->date('tanggal_bayar')->nullable();
            $table->string('metode_pembayaran')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kemitraan_payments');
    }
};
