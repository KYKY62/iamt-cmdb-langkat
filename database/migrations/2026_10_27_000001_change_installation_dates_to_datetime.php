 <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    /**
     * Ubah installed_at & removed_at di consumer_network_installations dari DATE ke DATETIME
     * supaya jam pemasangan / pelepasan ikut tersimpan.
     *
     * Aman dijalankan kapan saja:
     * - tabel belum ada                 -> dilewati
     * - kolom sudah DATETIME/TIMESTAMP  -> dilewati (misal sudah diubah manual)
     * Data lama tetap ada, tanggal lama menjadi "YYYY-MM-DD 00:00:00".
     */
    return new class extends Migration
    {
        private const TABLE = 'consumer_network_installations';

        private const COLUMNS = ['installed_at', 'removed_at'];

        public function up(): void
        {
            if (! Schema::hasTable(self::TABLE)) {
                return;
            }

            $columns = array_values(array_filter(
                self::COLUMNS,
                fn(string $column) => Schema::hasColumn(self::TABLE, $column)
                    && strtolower(Schema::getColumnType(self::TABLE, $column)) === 'date',
            ));

            if ($columns === []) {
                return;
            }

            Schema::table(self::TABLE, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->dateTime($column)->nullable()->change();
                }
            });
        }

        public function down(): void
        {
            // Sengaja dikosongkan: kembali ke DATE akan menghapus jam yang sudah tersimpan.
        }
    };
