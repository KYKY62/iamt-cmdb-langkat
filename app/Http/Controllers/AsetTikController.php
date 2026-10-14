<?php

namespace App\Http\Controllers;

use App\Models\AsetTik;
use App\Models\Opd;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AsetTikController extends Controller
{
    /**
     * Kandidat nama kolom OPD pada tabel aset_tik (dipakai untuk total_opd & filter ?opd=).
     */
    private const OPD_COLUMNS = ['opd_id', 'id_opd', 'opd', 'nama_opd'];

    /**
     * GET /api/aset-tik
     * Query opsional: ?opd=<nilai kolom opd> (sama persis), ?search=<sebagian nama OPD>
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $table = (new AsetTik)->getTable();
            $opdColumn = $this->opdColumn($table);

            $query = AsetTik::query();

            if ($opdColumn && $request->filled('opd')) {
                $query->where($opdColumn, $request->input('opd'));
            }

            // Pencarian sebagian berdasarkan nama OPD, contoh: ?search=kesehatan / ?search=stabat
            if ($request->filled('search')) {
                $keyword = trim((string) $request->input('search'));
                $escaped = addcslashes($keyword, '\\%_');
                $query->where('nama_opd', 'like', "%{$escaped}%");
            }

            // Ringkasan dihitung dari query yang sama (ikut filter).
            $selects = [
                'COALESCE(SUM(total_barang), 0) AS total_barang',
                'COALESCE(SUM(total_harga_barang), 0) AS total_harga',
            ];

            if ($opdColumn) {
                $wrapped = DB::getQueryGrammar()->wrap($opdColumn);
                $selects[] = "COUNT(DISTINCT {$wrapped}) AS total_opd";
            }

            $summary = (clone $query)->toBase()->selectRaw(implode(', ', $selects))->first();

            if (Schema::hasColumn($table, 'id')) {
                $query->orderBy('id');
            }

            $data = $this->groupByOpd($query->get(), $table, $opdColumn);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => $data->isEmpty()
                    ? ($request->filled('search')
                        ? 'Data aset TIK untuk OPD "'.$request->input('search').'" tidak ditemukan.'
                        : 'Data aset TIK kosong.')
                    : 'Data aset TIK berhasil diambil.',
                'summary' => [
                    'total_barang' => (int) $summary->total_barang,
                    'total_harga' => (float) $summary->total_harga,
                    'total_opd' => (int) ($summary->total_opd ?? 0),
                ],
                'data' => $data,
            ], 200);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/aset-tik/{opd}
     * {opd} boleh berupa opd_id dari config (contoh: 54) atau nama OPD
     * (contoh: Badan Kepegawaian Daerah). Hasil: satu objek OPD dengan list_barang.
     */
    public function show(string $opd): JsonResponse
    {
        try {
            $table = (new AsetTik)->getTable();
            $opdColumn = $this->opdColumn($table) ?? 'nama_opd';

            // Nama OPD persis seperti yang tersimpan di tabel aset_tik.
            $names = $this->resolveOpdNames($opd, $opdColumn);

            if ($names === []) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Data aset TIK untuk OPD "'.$opd.'" tidak ditemukan.',
                    'data' => null,
                ], 404);
            }

            $rows = AsetTik::query()
                ->whereIn($opdColumn, $names)
                ->orderBy('id')
                ->get();

            $data = $this->groupByOpd($rows, $table, $opdColumn)->first();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Data aset TIK OPD berhasil diambil.',
                'data' => $data,
            ], 200);
        } catch (Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * Cari nama OPD di aset_tik yang cocok dengan parameter:
     * - angka yang ada di config opd_langkat  -> dipakai nama OPD dari config
     * - selain itu                            -> dianggap nama OPD
     * Pencocokan memakai normalizeOpdName, jadi "Kec. Stabat" = "kec stabat".
     */
    private function resolveOpdNames(string $opd, string $opdColumn): array
    {
        $ref = collect(config('opd_langkat', []))->firstWhere('id_opd', trim($opd));
        $target = $this->normalizeOpdName($ref['nama_opd'] ?? $opd);

        return AsetTik::query()
            ->distinct()
            ->pluck($opdColumn)
            ->filter(fn ($name) => $name !== null && $this->normalizeOpdName((string) $name) === $target)
            ->values()
            ->all();
    }

    /**
     * Kelompokkan baris aset_tik per OPD:
     * [{ opd_id, nama_opd, total_barang, total_harga, list_barang: [{ jenis_barang, total_barang, total_harga_barang }] }]
     */
    private function groupByOpd(Collection $rows, string $table, ?string $opdColumn): Collection
    {
        // Kolom identitas OPD yang dipindah ke level grup (tidak diulang di list_barang).
        $opdFields = array_values(array_filter(
            self::OPD_COLUMNS,
            fn(string $column) => Schema::hasColumn($table, $column),
        ));

        $idColumn = collect(['opd_id', 'id_opd'])->first(fn($c) => in_array($c, $opdFields, true));
        $nameColumn = collect(['nama_opd', 'opd'])->first(fn($c) => in_array($c, $opdFields, true));

        // Ambil nama OPD dari tabel opd bila aset_tik hanya menyimpan id-nya.
        $opdNames = $idColumn && ! $nameColumn
            ? Opd::whereIn('id', $rows->pluck($idColumn)->filter()->unique())->pluck('nama', 'id')
            : collect();

        // Referensi id_opd (config/opd_langkat.php), dicocokkan lewat nama OPD yang dinormalisasi.
        $opdIdByName = collect(config('opd_langkat', []))
            ->mapWithKeys(fn(array $opd) => [$this->normalizeOpdName($opd['nama_opd']) => $opd]);

        return $rows
            ->groupBy(fn(AsetTik $row) => $opdColumn ? $this->normalizeOpdName((string) $row->{$opdColumn}) : '')
            ->map(function (Collection $items) use ($idColumn, $nameColumn, $opdNames, $opdFields, $opdIdByName) {
                $first = $items->first();
                $opdId = $idColumn ? $first->{$idColumn} : null;
                $namaOpd = $nameColumn ? $first->{$nameColumn} : ($opdNames[$opdId] ?? null);

                $ref = $namaOpd !== null ? ($opdIdByName[$this->normalizeOpdName($namaOpd)] ?? null) : null;

                return [
                    'opd_id' => $opdId ?? $ref['id_opd'] ?? null,
                    'nama_opd' => $namaOpd,
                    'lat' => $ref['lat'] ?? null,
                    'lng' => $ref['lng'] ?? null,
                    'total_barang' => (int) $items->sum('total_barang'),
                    'total_harga' => (float) $items->sum('total_harga_barang'),
                    'list_barang' => $items
                        ->map(fn(AsetTik $row) => collect($row->toArray())->except($opdFields)->all())
                        ->values(),
                ];
            })
            ->sortBy(fn(array $group) => mb_strtolower((string) $group['nama_opd']))
            ->values();
    }

    /**
     * "Kec. Stabat" / "Kec.Stabat" / "kec stabat" dianggap sama.
     */
    private function normalizeOpdName(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($name));
    }

    private function opdColumn(string $table): ?string
    {
        foreach (self::OPD_COLUMNS as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function serverError(Throwable $e): JsonResponse
    {
        Log::error('AsetTik API error', ['exception' => $e]);

        return response()->json([
            'status' => 'error',
            'code' => 500,
            'message' => 'Terjadi kesalahan pada server saat mengambil data aset TIK.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
