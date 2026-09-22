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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('kecamatan')->nullable()->after('alamat');
            $table->string('desa_kelurahan')->nullable()->after('kecamatan');
            $table->string('rt_rw')->nullable()->after('desa_kelurahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['kecamatan', 'desa_kelurahan', 'rt_rw']);
        });
    }
};
