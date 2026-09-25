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
            $table->json('tim_teknisi')->nullable()->after('ukuran_seragam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kemitraan_profiles', function (Blueprint $table) {
            $table->dropColumn('tim_teknisi');
        });
    }
};
