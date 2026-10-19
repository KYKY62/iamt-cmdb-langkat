<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel sudah ada (misal hasil import detail_aset_tik.sql) -> lewati tanpa error.
        if (Schema::hasTable('detail_aset_tik')) {
            return;
        }

        Schema::create('detail_aset_tik', function (Blueprint $table) {
            $table->char('Code_ID', 36)->primary();
            $table->string('Nm_Aset5', 150);
            $table->string('Nm_Unit', 150);
            $table->unsignedSmallInteger('Kd_Bidang');
            $table->unsignedSmallInteger('Kd_Unit');
            $table->string('Merk', 100)->nullable();
            $table->string('Type', 100)->nullable();
            $table->text('Keterangan')->nullable();
            $table->string('lastKondisi', 30);

            $table->index(['Kd_Bidang', 'Kd_Unit'], 'idx_detail_aset_tik_unit');
            $table->index('Nm_Unit', 'idx_detail_aset_tik_nm_unit');
            $table->index('Nm_Aset5', 'idx_detail_aset_tik_nm_aset5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_aset_tik');
    }
};
