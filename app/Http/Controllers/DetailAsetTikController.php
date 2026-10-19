<?php

namespace App\Http\Controllers;

use App\Models\DetailAsetTik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DetailAsetTikController extends Controller
{
    private const PER_PAGE = 20;

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
     * GET /api/aset-tik/{kdbidang}/{kdunit}/{nmaset5}?page=1
     *
     * {kdbidang} -> kolom Kd_Bidang (sama dengan kd_unit1 di tabel aset_tik)
     * {kdunit}   -> kolom Kd_Unit   (sama dengan kd_unit2 di tabel aset_tik)
     * {nmaset5}  -> kolom Nm_Aset5 / jenis barang, contoh: "Lap Top", "P.C Unit"
     *
     * Gabungan kdbidang + kdunit menunjuk tepat ke satu OPD.
     * Contoh: /api/aset-tik/7/1/Lap%20Top -> laptop Dinas Kesehatan.
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
                return response()->json([
                    'status' => 'error',
                    'code' => 422,
                    'message' => 'Parameter kdbidang dan kdunit harus berupa angka.',
                    'data' => null,
                ], 422);
            }

            $paginator = DetailAsetTik::query()
                ->select(self::COLUMNS)
                ->where('Kd_Bidang', (int) $kdbidang)
                ->where('Kd_Unit', (int) $kdunit)
                ->where('Nm_Aset5', $nmaset5)
                ->orderBy('Code_ID')
                ->paginate(self::PER_PAGE)
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
                ],
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
}
