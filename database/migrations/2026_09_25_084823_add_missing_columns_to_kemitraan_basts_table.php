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
        Schema::table('kemitraan_basts', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->change();
            
            // Add missing columns for standalone BAST menu
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->date('tanggal')->nullable();
            $table->string('judul')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('file_bast')->nullable();
            $table->string('status')->default('Selesai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kemitraan_basts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'tanggal', 'judul', 'keterangan', 'file_bast', 'status']);
        });
    }
};
