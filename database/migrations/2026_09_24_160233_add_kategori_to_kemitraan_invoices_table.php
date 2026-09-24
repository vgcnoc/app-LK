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
        Schema::table('kemitraan_invoices', function (Blueprint $table) {
            $table->string('kategori')->nullable();
            $table->string('tipe_pembayaran')->default('1 Kali');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kemitraan_invoices', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'tipe_pembayaran']);
        });
    }
};
