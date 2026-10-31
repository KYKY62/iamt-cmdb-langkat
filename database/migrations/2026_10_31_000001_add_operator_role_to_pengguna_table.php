<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah role "operator" ke kolom pengguna.role (ENUM).
 * Data user lama (full / read_only) tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pengguna') || ! Schema::hasColumn('pengguna', 'role')) {
            return;
        }

        Schema::table('pengguna', function (Blueprint $table) {
            $table->enum('role', ['full', 'read_only', 'operator'])
                ->default('read_only')
                ->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pengguna') || ! Schema::hasColumn('pengguna', 'role')) {
            return;
        }

        // User operator dijadikan read_only dulu supaya data tidak rusak saat ENUM diperkecil.
        DB::table('pengguna')->where('role', 'operator')->update(['role' => 'read_only']);

        Schema::table('pengguna', function (Blueprint $table) {
            $table->enum('role', ['full', 'read_only'])
                ->default('read_only')
                ->change();
        });
    }
};
