<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aset_tik')) {
            return;
        }

        Schema::create('aset_tik', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('kd_unit1')->nullable();
            $table->unsignedInteger('kd_unit2')->nullable();
            $table->string('nama_opd')->index();
            $table->string('jenis_barang');
            $table->unsignedInteger('total_barang')->default(0);
            $table->decimal('total_harga_barang', 18, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_tik');
    }
};
