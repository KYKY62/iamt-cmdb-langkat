<?php

namespace App\Http\Controllers;

use App\Models\DetailAsetTik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DetailAsetTikController extends Controller
{
    private const PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    /** Batas jumlah tahun dalam satu permintaan ?tahun= (mencegah query berlebihan). */
    private const MAX_YEARS = 30;

    private const MIN_YEAR = 1900;

    private const MAX_YEAR = 2100;

    /**
     * Kolom yang ditampilkan di response (urutan tetap).
     */
    private const COLUMNS = [
        'Code_ID',
        'Nm_Aset5',
        'Nm_Unit',
        'Kd_Bidang',
        'Kd_Unit',
        'Tgl_Pembukuan',
        'Merk',
        'Type',
        'Keterangan',
        'lastKondisi',
    ];

    /**
     * GET /api/aset-tik/{kdbidang}/{kdunit}/{nmaset5}
     *
     * {kdbidang} -> kolom Kd_Bidang (sama dengan kd_unit1 di tabel aset_tik)
     * {kdunit}   -> kolom Kd_Unit   (sama dengan kd_unit2 di tabel aset_tik)
     * {nmaset5}  -> kolom Nm_Aset5 / jenis barang, contoh: "Lap Top", "P.C Unit"
     *
     * Gabungan kdbidang + kdunit menunjuk tepat ke satu OPD.
     * Contoh: /api/aset-tik/7/1/Lap%20Top -> laptop Dinas Kesehatan.
     *
     * Query opsional (semuanya boleh tidak dikirim, perilaku lama tetap sama):
     *   ?tahun=2025,2024,2023  tahun Tgl_Pembukuan, tahun berapa pun (4 digit, pisah koma, maks. 30)
     *   ?kondisi=Baik          saring lastKondisi (sama persis, mis. "Baik", "Rusak Ringan")
     *   ?per_page=20           jumlah data per halaman (1-50, default 20)
     *   ?page=1                halaman
     *
     * Data diurutkan dari Tgl_Pembukuan terbaru, lalu Code_ID.
     * Blok "summary" berisi jumlah per tahun & per kondisi dari seluruh data yang cocok
     * (OPD + jenis barang + tahun), bukan hanya halaman yang sedang tampil.
     *
     * Catatan: karakter "#" pada nama aset harus di-encode menjadi %23
     * (contoh: "Personal Komputer Lain-lain#" -> Personal%20Komputer%20Lain-lain%23).
     */
    public function index(Request $request, string $kdbidang, string $kdunit, string $nmaset5): JsonResponse
    {
        try {
            $kdbidang = trim($kdbidang);
            $kdunit = trim($kdunit);
            $nmaset5 = trim($nmaset5);

            if (! ctype_digit($kdbidang) || ! ctype_digit($kdunit)) {
                return $this->validationError('Parameter kdbidang dan kdunit harus berupa angka.');
            }

            $years = $this->parseYears($request->query('tahun'));
            if ($years === false) {
                return $this->validationError(
                    'Parameter tahun tidak valid. Gunakan tahun 4 digit dipisah koma, contoh: ?tahun=2025,2024,2023'
                );
            }

            $kondisi = trim((string) $request->query('kondisi', ''));
            $perPage = $this->parsePerPage($request->query('per_page'));

            // Cakupan dasar: OPD + jenis barang + (opsional) tahun.
            $scope = DetailAsetTik::query()
                ->where('Kd_Bidang', (int) $kdbidang)
                ->where('Kd_Unit', (int) $kdunit)
                ->where('Nm_Aset5', $nmaset5);

            if ($years !== []) {
                $this->whereYears($scope, $years);
            }

            $paginator = (clone $scope)
                ->select(self::COLUMNS)
                ->when($kondisi !== '', fn (Builder $q) => $q->where('lastKondisi', $kondisi))
                ->orderByDesc('Tgl_Pembukuan')
                ->orderBy('Code_ID')
                ->paginate($perPage)
                ->withQueryString();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => $paginator->total() === 0
                    ? 'Detail aset TIK "'.$nmaset5.'" untuk unit '.$kdbidang.'.'.$kdunit.' tidak ditemukan.'
                    : 'Detail aset TIK berhasil diambil.',
                'filter' => [
                    'kdbidang' => (int) $kdbidang,
                    'kdunit' => (int) $kdunit,
                    'nmaset5' => $nmaset5,
                    'tahun' => $years,
                    'kondisi' => $kondisi !== '' ? $kondisi : null,
                ],
                'summary' => $this->summary($scope),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ],
                'data' => $paginator->items(),
            ], 200);
        } catch (Throwable $e) {
            Log::error('DetailAsetTik API error', ['exception' => $e]);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Terjadi kesalahan pada server saat mengambil detail aset TIK.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * "2025, 2024,2023" -> [2025, 2024, 2023]
     *
     * - null / kosong       -> [] (tanpa filter tahun)
     * - nilai tidak valid   -> diabaikan (mis. "2025,abc" -> [2025])
     * - semua tidak valid   -> false (dibalas 422)
     *
     * @return list<int>|false
     */
    private function parseYears(mixed $value): array|false
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return [];
        }

        // ?tahun[]=2025&tahun[]=2024 juga diterima.
        $parts = is_array($value) ? $value : explode(',', (string) $value);

        $years = collect($parts)
            ->map(fn ($part) => trim((string) $part))
            ->filter(fn (string $part) => preg_match('/^\d{4}$/', $part) === 1)
            ->map(fn (string $part) => (int) $part)
            ->filter(fn (int $year) => $year >= self::MIN_YEAR && $year <= self::MAX_YEAR)
            ->unique()
            ->sortDesc()
            ->take(self::MAX_YEARS)
            ->values()
            ->all();

        return $years === [] ? false : $years;
    }

    private function parsePerPage(mixed $value): int
    {
        if (! is_string($value) || ! ctype_digit($value)) {
            return self::PER_PAGE;
        }

        return max(1, min((int) $value, self::MAX_PER_PAGE));
    }

    /**
     * Saring tahun memakai rentang tanggal (bukan YEAR()) supaya tetap bisa memakai index.
     * Batas atas memakai "< 1 Januari tahun berikutnya" agar aman juga untuk kolom DATETIME.
     *
     * @param  list<int>  $years
     */
    private function whereYears(Builder $query, array $years): void
    {
        $query->where(function (Builder $q) use ($years) {
            foreach ($years as $year) {
                $q->orWhere(function (Builder $range) use ($year) {
                    $range->where('Tgl_Pembukuan', '>=', sprintf('%04d-01-01', $year))
                        ->where('Tgl_Pembukuan', '<', sprintf('%04d-01-01', $year + 1));
                });
            }
        });
    }

    /**
     * Ringkasan dari seluruh data dalam cakupan (tanpa filter kondisi & tanpa paginasi),
     * dipakai untuk angka di tombol filter tahun / kondisi.
     */
    private function summary(Builder $scope): array
    {
        $yearExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', Tgl_Pembukuan) AS INTEGER)"
            : 'YEAR(Tgl_Pembukuan)';

        $perTahun = (clone $scope)
            ->toBase()
            ->selectRaw("{$yearExpression} AS tahun, COUNT(*) AS total")
            ->groupByRaw($yearExpression)
            ->orderByRaw("{$yearExpression} DESC")
            ->get()
            ->map(fn ($row) => [
                'tahun' => $row->tahun !== null ? (int) $row->tahun : null,
                'total' => (int) $row->total,
            ])
            ->values();

        $perKondisi = (clone $scope)
            ->toBase()
            ->selectRaw('lastKondisi AS kondisi, COUNT(*) AS total')
            ->groupBy('lastKondisi')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'kondisi' => $row->kondisi,
                'total' => (int) $row->total,
            ])
            ->values();

        return [
            'total' => (int) $perTahun->sum('total'),
            'per_tahun' => $perTahun,
            'per_kondisi' => $perKondisi,
        ];
    }

    private function validationError(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => $message,
            'data' => null,
        ], 422);
    }
}
