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
        Schema::table('kemitraan_profiles', function (Blueprint $table) {
            $table->string('ukuran_seragam')->nullable()->after('jumlah_teknisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kemitraan_profiles', function (Blueprint $table) {
            $table->dropColumn('ukuran_seragam');
        });
    }
};
