<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Untuk database yang sudah pernah menjalankan migration create_detail_aset_tik
 * sebelum kolom Tgl_Pembukuan ada. Aman dijalankan kapan saja:
 * dilewati bila tabel belum ada atau kolom sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('detail_aset_tik') || Schema::hasColumn('detail_aset_tik', 'Tgl_Pembukuan')) {
            return;
        }

        Schema::table('detail_aset_tik', function (Blueprint $table) {
            $table->date('Tgl_Pembukuan')->nullable()->after('Kd_Unit');
        });
    }

    public function down(): void
    {
        // Sengaja dikosongkan: rollback tidak menghapus kolom/data Tgl_Pembukuan.
    }
};
