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
            $table->string('status_akun')->default('Aktif')->after('user_id');
            $table->string('tipe_kemitraan')->default('Reseller ISP')->after('status_akun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kemitraan_profiles', function (Blueprint $table) {
            //
        });
    }
};
