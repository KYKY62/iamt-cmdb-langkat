<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Menampilkan file dokumen yang disimpan di storage/app/uploads (atau public/uploads untuk data lama)
 * secara inline, supaya PDF/gambar bisa langsung dibuka di browser.
 */
trait ServesStoredDocument
{
    protected function serveStoredDocument(?string $path, ?string $originalName, ?string $mimeType): BinaryFileResponse|JsonResponse
    {
        $fullPath = $this->resolveDocumentPath($path);

        if (! $fullPath) {
            return response()->json([
                'message' => 'File dokumen tidak ditemukan di server.',
            ], 404);
        }

        $name = $originalName ?: basename($fullPath);
        $fallbackName = Str::ascii($name) ?: basename($fullPath);
        $fallbackName = str_replace(['"', '/', '\\', '%'], '_', $fallbackName);

        $response = response()->file($fullPath, [
            'Content-Type' => $mimeType ?: (mime_content_type($fullPath) ?: 'application/octet-stream'),
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $name, $fallbackName);

        return $response;
    }

    /**
     * Hanya mengizinkan file di dalam folder uploads (mencegah path traversal).
     */
    private function resolveDocumentPath(?string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');

        if ($path === '' || ! str_starts_with($path, 'uploads/')) {
            return null;
        }

        $candidates = [
            [storage_path('app/'.$path), storage_path('app/uploads')],
            [public_path($path), public_path('uploads')],
        ];

        foreach ($candidates as [$candidate, $allowedRoot]) {
            $real = realpath($candidate);
            $root = realpath($allowedRoot);

            if ($real && $root && is_file($real) && str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
                return $real;
            }
        }

        return null;
    }
}
