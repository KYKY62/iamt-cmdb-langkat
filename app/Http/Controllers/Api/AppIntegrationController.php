<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ServesStoredDocument;
use App\Http\Controllers\Controller;
use App\Models\AppIntegration;
use App\Models\AppIntegrationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AppIntegrationController extends Controller
{
    use ServesStoredDocument;

    public function index()
    {
        return AppIntegration::with([
            'aplikasi:id,nama,jenis_aplikasi',
            'targetApplications:id,nama,jenis_aplikasi',
            'dataAssets:id,name,aplikasi_id,classification_id',
            'dataAssets.classification:id,code,name,risk_level',
            'documents',
        ])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function show(AppIntegration $appIntegration)
    {
        return $appIntegration->load($this->relations());
    }

    /**
     * GET /api/app-integration-documents/{appIntegrationDocument}/file
     * Menampilkan file dokumen interoperabilitas (PDF/gambar) secara inline di browser.
     */
    public function documentFile(AppIntegrationDocument $appIntegrationDocument)
    {
        return $this->serveStoredDocument(
            $appIntegrationDocument->path,
            $appIntegrationDocument->original_name,
            $appIntegrationDocument->mime_type,
        );
    }

    public function store(Request $request)
    {
        $integration = AppIntegration::create($this->validated($request));
        $this->syncRelations($integration, $request);
        $this->storeDocuments($integration, $request);

        return response()->json($integration->fresh($this->relations()), 201);
    }

    public function update(Request $request, AppIntegration $appIntegration)
    {
        $appIntegration->update($this->validated($request));
        $this->syncRelations($appIntegration, $request);
        $this->storeDocuments($appIntegration, $request);

        return $appIntegration->fresh($this->relations());
    }

    public function destroy(AppIntegration $appIntegration)
    {
        $appIntegration->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'aplikasi_id' => ['required', 'uuid', 'exists:aplikasi,id'],
            'deskripsi' => ['nullable', 'string'],
            'jenis_integrasi' => ['required', 'in:proses_bisnis,berbagi_data'],
            'metode_integrasi' => ['required', 'in:spl,host_to_host'],
            'external_endpoints' => ['nullable', 'string'],
            'target_application_ids' => ['array'],
            'target_application_ids.*' => ['uuid', 'exists:aplikasi,id'],
            'data_asset_ids' => ['array'],
            'data_asset_ids.*' => ['integer', 'exists:data_assets,id'],
            'documents' => ['array'],
            'documents.*' => ['file', 'max:20480'],
        ]);
    }

    private function syncRelations(AppIntegration $integration, Request $request): void
    {
        $integration->targetApplications()->sync($request->input('target_application_ids', []));
        $integration->dataAssets()->sync($request->input('data_asset_ids', []));
    }

    private function storeDocuments(AppIntegration $integration, Request $request): void
    {
        foreach ($request->file('documents', []) as $file) {
            // Baca info file sebelum dipindah (file .tmp hilang setelah move()).
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            $path = $this->storeUpload($file, 'integration-documents');
            AppIntegrationDocument::create([
                'integration_id' => $integration->id,
                'path' => $path,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size_bytes' => $size,
            ]);
        }
    }

    private function storeUpload($file, string $folder): string
    {
        $directory = storage_path("app/uploads/{$folder}");
        File::ensureDirectoryExists($directory);
        $filename = (string) Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return "uploads/{$folder}/{$filename}";
    }

    private function relations(): array
    {
        return ['aplikasi', 'targetApplications', 'dataAssets.classification', 'documents'];
    }
}
