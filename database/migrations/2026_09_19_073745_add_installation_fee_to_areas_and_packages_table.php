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
        Schema::table('areas', function (Blueprint $table) {
            $table->integer('installation_fee')->nullable()->after('description');
        });

        Schema::table('internet_packages', function (Blueprint $table) {
            $table->integer('installation_fee')->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('installation_fee');
        });

        Schema::table('internet_packages', function (Blueprint $table) {
            $table->dropColumn('installation_fee');
        });
    }
};
