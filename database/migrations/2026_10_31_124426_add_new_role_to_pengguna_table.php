<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->enum('role', ['full', 'read_only', 'operator'])
                ->default('read_only')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            DB::table('pengguna')->where('role', 'operator')->update(['role' => 'read_only']);

            Schema::table('pengguna', function (Blueprint $table) {
                $table->enum('role', ['full', 'read_only'])
                    ->default('read_only')
                    ->change();
            });
        });
    }
};
