<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ServesStoredDocument;
use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ApplicationDocumentController extends Controller
{
    use ServesStoredDocument;

    public function index()
    {
        return ApplicationDocument::with('aplikasi:id,nama,jenis_aplikasi,status')
            ->orderByDesc('tanggal')
            ->get();
    }

    public function show(ApplicationDocument $applicationDocument)
    {
        return $applicationDocument->load('aplikasi:id,nama,jenis_aplikasi,status');
    }

    /**
     * GET /api/application-documents/{applicationDocument}/file
     * Menampilkan file dokumen (PDF/gambar) secara inline di browser.
     */
    public function file(ApplicationDocument $applicationDocument)
    {
        return $this->serveStoredDocument(
            $applicationDocument->path ?: $applicationDocument->url,
            $applicationDocument->original_name,
            $applicationDocument->mime_type,
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'aplikasi_id' => ['required', 'uuid', 'exists:aplikasi,id'],
            'document_category' => ['required', 'in:petunjuk_teknis,tata_kelola,keamanan'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx',
                'max:20480',
            ],
        ]);

        $documents = collect($request->file('files'))->map(function ($file) use ($data) {
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            $stored = $this->storeUpload($file, 'application-documents');

            return ApplicationDocument::create([
                'aplikasi_id' => $data['aplikasi_id'],
                'jenis' => $this->legacyCategory($data['document_category']),
                'document_category' => $data['document_category'],
                'nama' => pathinfo($originalName, PATHINFO_FILENAME),
                'url' => $stored,
                'path' => $stored,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size_bytes' => $size,
                'tanggal' => now()->toDateString(),
            ]);
        });

        $documents->each->load('aplikasi');

        return response()->json($documents, 201);
    }

    public function update(Request $request, ApplicationDocument $applicationDocument)
    {
        $data = $request->validate([
            'aplikasi_id' => ['required', 'uuid', 'exists:aplikasi,id'],
            'document_category' => ['required', 'in:petunjuk_teknis,tata_kelola,keamanan'],
        ]);

        $applicationDocument->update([
            'aplikasi_id' => $data['aplikasi_id'],
            'jenis' => $this->legacyCategory($data['document_category']),
            'document_category' => $data['document_category'],
        ]);

        return $applicationDocument->fresh('aplikasi');
    }

    public function destroy(ApplicationDocument $applicationDocument)
    {
        $applicationDocument->delete();

        return response()->noContent();
    }

    private function legacyCategory(string $category): string
    {
        return match ($category) {
            'keamanan' => 'keamanan',
            'tata_kelola' => 'tata_kelola',
            default => 'modul',
        };
    }

    private function storeUpload($file, string $folder): string
    {
        $directory = storage_path("app/uploads/{$folder}");
        File::ensureDirectoryExists($directory);
        $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return "uploads/{$folder}/{$filename}";
    }
}
