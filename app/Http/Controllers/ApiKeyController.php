<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index(Request $request)
    {
        return ApiKey::query()
            ->where('user_id', $request->attributes->get('auth_user')->id)
            ->where('key_prefix', 'like', 'iamt_key_%')
            ->latest()
            ->get(['id', 'key_prefix', 'created_at', 'last_used_at'])
            ->map(fn (ApiKey $apiKey) => [
                'id' => $apiKey->id,
                'api_key_masked' => $apiKey->key_prefix.'****************',
                'created_at' => $apiKey->created_at,
                'last_used_at' => $apiKey->last_used_at,
            ]);
    }

    public function store(Request $request)
    {
        $plainKey = 'iamt_key_'.Str::random(40);
        $user = $request->attributes->get('auth_user');

        ApiKey::create([
            'user_id' => $user->id,
            'key_prefix' => substr($plainKey, 0, 12),
            'key_hash' => hash('sha256', $plainKey),
        ]);

        return response()->json([
            'message' => 'API key berhasil dibuat. Simpan sekarang karena tidak akan ditampilkan lagi.',
            'api_key' => $plainKey,
        ], 201);
    }

    public function destroy(Request $request, ApiKey $apiKey)
    {
        abort_unless(
            $apiKey->user_id === $request->attributes->get('auth_user')->id,
            404,
        );

        $apiKey->delete();

        return response()->noContent();
    }
}
