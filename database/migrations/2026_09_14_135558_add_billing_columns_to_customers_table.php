<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('last_paid_date')->nullable();
            $table->decimal('base_amount', 15, 2)->default(0);
        });

        // Set base_amount = amount for existing records
        DB::statement('UPDATE customers SET base_amount = amount');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['last_paid_date', 'base_amount']);
        });
    }
};
