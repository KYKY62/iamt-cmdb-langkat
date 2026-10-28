<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah reported_at (waktu laporan masuk) di consumer_network_installations.
 * Dipakai untuk menghitung kecepatan tindak lanjut: installed_at - reported_at.
 * Kosong = pemasangan rutin (bukan dari laporan), tidak ikut dihitung.
 *
 * Aman dijalankan kapan saja: dilewati bila tabel belum ada atau kolom sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consumer_network_installations')
            || Schema::hasColumn('consumer_network_installations', 'reported_at')) {
            return;
        }

        Schema::table('consumer_network_installations', function (Blueprint $table) {
            $table->dateTime('reported_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        // Sengaja dikosongkan: rollback tidak menghapus data waktu laporan yang sudah diisi.
    }
};
