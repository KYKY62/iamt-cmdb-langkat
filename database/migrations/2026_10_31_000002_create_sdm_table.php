<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul SDM: nama, jabatan, pendidikan, dan file sertifikasi (PDF, opsional).
 * Aman dijalankan kapan saja: dilewati bila tabel sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sdm')) {
            return;
        }

        Schema::create('sdm', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->string('jabatan');
            $table->enum('pendidikan', ['SD', 'SMP', 'SMA', 'S1', 'S2', 'S3']);
            $table->string('sertifikasi_path')->nullable();
            $table->string('sertifikasi_original_name')->nullable();
            $table->unsignedBigInteger('sertifikasi_size_bytes')->nullable();
            $table->timestamps();

            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdm');
    }
};
