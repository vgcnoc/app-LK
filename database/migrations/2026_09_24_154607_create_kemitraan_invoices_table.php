<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kemitraan_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nomor_invoice')->unique();
            $table->date('tanggal_tagihan')->nullable();
            $table->date('jatuh_tempo')->nullable();
            $table->decimal('nominal', 15, 2)->default(0);
            $table->string('judul')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->default('Belum Bayar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kemitraan_invoices');
    }
};
