<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ServesStoredDocument;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Sdm;
use App\Models\SdmSertifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SdmController extends Controller
{
    use ServesStoredDocument;

    private const UPLOAD_FOLDER = 'sdm-sertifikasi';

    /** Maksimal file sertifikasi yang diunggah dalam satu kali simpan. */
    private const MAX_FILES_PER_REQUEST = 10;

    private const RELATIONS = ['opd:id,nama', 'sertifikasi:id,sdm_id,original_name,size_bytes,created_at'];

    public function index(Request $request)
    {
        // Operator: hanya SDM milik OPD-nya. Full & read_only: semua.
        return $this->authUser($request)
            ->limitToOwnOpd(Sdm::query()->with(self::RELATIONS))
            ->orderBy('nama')
            ->get();
    }

    public function show(Request $request, Sdm $sdm)
    {
        $this->ensureCanAccess($request, $sdm);

        return $sdm->load(self::RELATIONS);
    }

    /**
     * GET /api/sdm/{sdm}/sertifikasi/{sertifikasi}
     * Menampilkan satu file PDF sertifikasi secara inline di browser.
     */
    public function sertifikasi(Request $request, Sdm $sdm, SdmSertifikasi $sertifikasi)
    {
        $this->ensureCanAccess($request, $sdm);
        $this->ensureBelongsTo($sdm, $sertifikasi);

        return $this->serveStoredDocument($sertifikasi->path, $sertifikasi->original_name, 'application/pdf');
    }

    /**
     * DELETE /api/sdm/{sdm}/sertifikasi/{sertifikasi}
     * Hapus satu sertifikasi tanpa mengubah data SDM lainnya.
     */
    public function destroySertifikasi(Request $request, Sdm $sdm, SdmSertifikasi $sertifikasi)
    {
        $this->ensureCanAccess($request, $sdm);
        $this->ensureBelongsTo($sdm, $sertifikasi);

        $before = $sdm->load(self::RELATIONS)->toArray();
        $path = $sertifikasi->path;
        $sertifikasi->delete();
        $this->deleteStoredFile($path);
        $this->audit('update', $sdm, $before, $sdm->fresh(self::RELATIONS)->toArray(), $request);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Sertifikasi berhasil dihapus.',
            'data' => $sdm->fresh(self::RELATIONS),
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $this->applyOwnOpd($request, $this->validated($request));
        $files = $request->file('sertifikasi', []);

        $sdm = DB::transaction(function () use ($data, $files) {
            $sdm = Sdm::create($this->sdmFields($data));
            $this->attachFiles($sdm, $files);

            return $sdm;
        });

        $this->audit('create', $sdm, null, $sdm->fresh(self::RELATIONS)->toArray(), $request);

        return response()->json($sdm->fresh(self::RELATIONS), 201);
    }

    /**
     * PUT /api/sdm/{sdm}
     * File di "sertifikasi[]" DITAMBAHKAN ke sertifikasi yang sudah ada.
     * ID di "hapus_sertifikasi_ids[]" dihapus.
     */
    public function update(Request $request, Sdm $sdm)
    {
        $this->ensureCanAccess($request, $sdm);

        $before = $sdm->load(self::RELATIONS)->toArray();
        $data = $this->applyOwnOpd($request, $this->validated($request));
        $files = $request->file('sertifikasi', []);

        $removed = $sdm->sertifikasi()
            ->whereIn('id', $data['hapus_sertifikasi_ids'] ?? [])
            ->get();

        DB::transaction(function () use ($sdm, $data, $files, $removed) {
            $sdm->update($this->sdmFields($data));
            $this->attachFiles($sdm, $files);
            SdmSertifikasi::whereIn('id', $removed->pluck('id'))->delete();
        });

        // File lama dihapus dari server hanya setelah data berhasil disimpan.
        $removed->each(fn (SdmSertifikasi $cert) => $this->deleteStoredFile($cert->path));

        $this->audit('update', $sdm, $before, $sdm->fresh(self::RELATIONS)->toArray(), $request);

        return $sdm->fresh(self::RELATIONS);
    }

    public function destroy(Request $request, Sdm $sdm)
    {
        $this->ensureCanAccess($request, $sdm);

        $before = $sdm->load(self::RELATIONS)->toArray();
        $paths = $sdm->sertifikasi()->pluck('path');

        $sdm->delete(); // baris sdm_sertifikasi ikut terhapus (cascade)
        $paths->each(fn ($path) => $this->deleteStoredFile($path));
        $this->audit('delete', $sdm, $before, null, $request);

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        $isOperator = $this->authUser($request)->restrictedToOwnOpd();

        return $request->validate([
            // Operator: OPD diambil otomatis dari akunnya, jadi tidak wajib dikirim.
            'opd_id' => [$isOperator ? 'nullable' : 'required', 'uuid', 'exists:opd,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'pendidikan' => ['required', Rule::in(Sdm::PENDIDIKAN)],
            'sertifikasi' => ['nullable', 'array', 'max:'.self::MAX_FILES_PER_REQUEST],
            'sertifikasi.*' => ['file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:20480'],
            'hapus_sertifikasi_ids' => ['nullable', 'array'],
            'hapus_sertifikasi_ids.*' => ['uuid'],
        ], [
            'opd_id.required' => 'OPD wajib dipilih.',
            'opd_id.exists' => 'OPD tidak ditemukan.',
            'pendidikan.in' => 'Pendidikan hanya boleh SD, SMP, SMA, S1, S2, atau S3.',
            'sertifikasi.array' => 'Sertifikasi harus dikirim sebagai sertifikasi[] (bisa lebih dari satu file).',
            'sertifikasi.max' => 'Maksimal '.self::MAX_FILES_PER_REQUEST.' file sertifikasi sekali simpan.',
            'sertifikasi.*.file' => 'Sertifikasi harus berupa file PDF.',
            'sertifikasi.*.mimes' => 'Sertifikasi harus berupa file PDF.',
            'sertifikasi.*.mimetypes' => 'Sertifikasi harus berupa file PDF.',
            'sertifikasi.*.max' => 'Ukuran setiap file sertifikasi maksimal 20 MB.',
        ]);
    }

    private function sdmFields(array $data): array
    {
        return array_intersect_key($data, array_flip(['opd_id', 'nama', 'jabatan', 'pendidikan']));
    }

    /**
     * Simpan setiap PDF ke storage/app/uploads/sdm-sertifikasi dan catat di tabel sdm_sertifikasi.
     * Info file dibaca SEBELUM move(), karena file .tmp hilang setelah dipindah.
     *
     * @param  UploadedFile[]  $files
     */
    private function attachFiles(Sdm $sdm, array $files): void
    {
        $directory = storage_path('app/uploads/'.self::UPLOAD_FOLDER);
        File::ensureDirectoryExists($directory);
        $moved = [];

        try {
            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $size = $file->getSize();
                $filename = (string) Str::uuid().'.pdf';
                $file->move($directory, $filename);
                $path = 'uploads/'.self::UPLOAD_FOLDER.'/'.$filename;
                $moved[] = $path;

                $sdm->sertifikasi()->create([
                    'path' => $path,
                    'original_name' => $originalName,
                    'size_bytes' => $size,
                ]);
            }
        } catch (Throwable $e) {
            // Bila gagal di tengah jalan, jangan tinggalkan file yatim di server.
            foreach ($moved as $path) {
                $this->deleteStoredFile($path);
            }

            throw $e;
        }
    }

    private function authUser(Request $request)
    {
        return $request->attributes->get('auth_user');
    }

    /**
     * Operator yang membuka SDM OPD lain mendapat 404 (data dianggap tidak ada).
     */
    private function ensureCanAccess(Request $request, Sdm $sdm): void
    {
        abort_unless($this->authUser($request)->canAccessOpd($sdm->opd_id), 404);
    }

    private function ensureBelongsTo(Sdm $sdm, SdmSertifikasi $sertifikasi): void
    {
        abort_unless($sertifikasi->sdm_id === $sdm->id, 404);
    }

    /**
     * Untuk operator, OPD selalu dipaksa ke OPD akunnya sendiri.
     */
    private function applyOwnOpd(Request $request, array $data): array
    {
        $user = $this->authUser($request);

        if (! $user->restrictedToOwnOpd()) {
            return $data;
        }

        if (blank($user->opd_id)) {
            throw ValidationException::withMessages([
                'opd_id' => ['Akun operator belum terhubung ke OPD. Hubungi admin.'],
            ]);
        }

        $data['opd_id'] = $user->opd_id;

        return $data;
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/'.self::UPLOAD_FOLDER.'/')) {
            File::delete(storage_path('app/'.$path));
        }
    }

    private function audit(string $action, Sdm $sdm, ?array $before, ?array $after, Request $request): void
    {
        AuditLog::create([
            'user_id' => $request->attributes->get('auth_user')?->id,
            'aksi' => $action,
            'tabel' => 'sdm',
            'record_id' => $sdm->id,
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
