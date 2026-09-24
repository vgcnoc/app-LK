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
            $table->string('metro')->default('Belum ada metro')->after('tipe_kemitraan');
            $table->string('bandwidth')->nullable()->after('metro');
            // Update default value for status_akun
            $table->string('status_akun')->default('Pending')->change();
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
