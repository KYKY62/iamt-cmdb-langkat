<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * SDM bisa punya lebih dari 1 sertifikasi (PDF).
 *
 * 1. Buat tabel sdm_sertifikasi (satu baris = satu file PDF).
 * 2. Pindahkan sertifikasi lama (kolom sertifikasi_* di tabel sdm) ke tabel baru.
 *    File PDF di server tidak dipindah atau dihapus, hanya datanya.
 * 3. Hapus kolom sertifikasi_* dari tabel sdm karena sudah tidak dipakai.
 */
return new class extends Migration
{
    private const OLD_COLUMNS = ['sertifikasi_path', 'sertifikasi_original_name', 'sertifikasi_size_bytes'];

    public function up(): void
    {
        if (! Schema::hasTable('sdm_sertifikasi')) {
            Schema::create('sdm_sertifikasi', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('sdm_id')->index();
                $table->string('path');
                $table->string('original_name');
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->timestamps();

                $table->foreign('sdm_id')->references('id')->on('sdm')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('sdm') || ! Schema::hasColumn('sdm', 'sertifikasi_path')) {
            return;
        }

        $now = now();
        DB::table('sdm')
            ->whereNotNull('sertifikasi_path')
            ->orderBy('id')
            ->each(function ($row) use ($now) {
                $exists = DB::table('sdm_sertifikasi')
                    ->where('sdm_id', $row->id)
                    ->where('path', $row->sertifikasi_path)
                    ->exists();

                if (! $exists) {
                    DB::table('sdm_sertifikasi')->insert([
                        'id' => (string) Str::orderedUuid(),
                        'sdm_id' => $row->id,
                        'path' => $row->sertifikasi_path,
                        'original_name' => $row->sertifikasi_original_name ?: basename($row->sertifikasi_path),
                        'size_bytes' => $row->sertifikasi_size_bytes,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

        $existing = array_values(array_filter(self::OLD_COLUMNS, fn ($c) => Schema::hasColumn('sdm', $c)));
        if ($existing !== []) {
            Schema::table('sdm', fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }

    public function down(): void
    {
        // Kembalikan kolom lama dan isi dengan sertifikasi pertama tiap SDM.
        if (Schema::hasTable('sdm') && ! Schema::hasColumn('sdm', 'sertifikasi_path')) {
            Schema::table('sdm', function (Blueprint $table) {
                $table->string('sertifikasi_path')->nullable();
                $table->string('sertifikasi_original_name')->nullable();
                $table->unsignedBigInteger('sertifikasi_size_bytes')->nullable();
            });

            if (Schema::hasTable('sdm_sertifikasi')) {
                DB::table('sdm_sertifikasi')
                    ->orderBy('created_at')
                    ->get()
                    ->unique('sdm_id')
                    ->each(fn ($cert) => DB::table('sdm')->where('id', $cert->sdm_id)->update([
                        'sertifikasi_path' => $cert->path,
                        'sertifikasi_original_name' => $cert->original_name,
                        'sertifikasi_size_bytes' => $cert->size_bytes,
                    ]));
            }
        }

        Schema::dropIfExists('sdm_sertifikasi');
    }
};
