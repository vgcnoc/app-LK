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
        Schema::create('kemitraan_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Data Mitra
            $table->string('nik')->nullable();
            $table->string('no_wa')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            
            // Data Usaha
            $table->string('nama_usaha')->nullable();
            $table->string('jenis_usaha')->nullable();
            $table->string('nib')->nullable();
            $table->string('npwp')->nullable();
            
            // Area Kemitraan
            $table->string('area_dikelola')->nullable();
            $table->string('kecamatan')->nullable();
            $table->text('pin_maps')->nullable();
            $table->integer('estimasi_pelanggan')->nullable();
            
            // Data Teknis
            $table->string('pic_teknisi')->nullable();
            $table->string('wa_teknisi')->nullable();
            $table->integer('jumlah_teknisi')->nullable();
            $table->text('pengalaman_infrastruktur')->nullable();
            
            // Dokumen (File Paths)
            $table->string('file_ktp')->nullable();
            $table->string('file_nib')->nullable();
            $table->string('file_npwp')->nullable();
            $table->string('file_lokasi')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kemitraan_profiles');
    }
};
