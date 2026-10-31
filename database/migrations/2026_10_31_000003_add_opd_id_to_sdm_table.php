<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah opd_id di tabel sdm (SDM milik OPD mana).
 * Data SDM lama tetap ada dengan opd_id kosong.
 * Aman dijalankan kapan saja: dilewati bila tabel belum ada atau kolom sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sdm') || Schema::hasColumn('sdm', 'opd_id')) {
            return;
        }

        Schema::table('sdm', function (Blueprint $table) {
            $table->uuid('opd_id')->nullable()->after('id')->index();
            $table->foreign('opd_id')->references('id')->on('opd')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sdm') || ! Schema::hasColumn('sdm', 'opd_id')) {
            return;
        }

        Schema::table('sdm', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
            $table->dropColumn('opd_id');
        });
    }
};
