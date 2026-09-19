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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('member_number')->unique()->nullable()->after('id');
            $table->string('bank_account')->nullable()->after('email');
            $table->date('join_date')->nullable()->after('bank_account');
            $table->unsignedBigInteger('parent_id')->nullable()->after('join_date');
            $table->foreign('parent_id')->references('id')->on('sales')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['member_number', 'bank_account', 'join_date', 'parent_id']);
        });
    }
};
