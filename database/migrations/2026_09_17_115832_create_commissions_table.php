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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('type'); // 'booking', 'monthly_direct', 'monthly_upline_1', 'monthly_upline_2'
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('pending'); // 'pending', 'paid'
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('sales_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
