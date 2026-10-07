<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = Pengguna::where('email', $credentials['email'])->first();

        if (! $user || $user->status !== 'aktif' || ! Hash::check($credentials['password'], $user->password ?? '')) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak sesuai.'],
            ]);
        }

        // Token sesi pun disimpan di tabel api_keys supaya semua autentikasi
        // melewati satu mekanisme. Prefix session_ membuatnya tidak muncul
        // pada daftar API key permanen pengguna.
        $token = 'iamt_session_'.Str::random(64);
        ApiKey::where('user_id', $user->id)
            ->where('key_prefix', 'like', 'session_%')
            ->delete();
        ApiKey::create([
            'user_id' => $user->id,
            'key_prefix' => 'session_',
            'key_hash' => hash('sha256', $token),
        ]);
        $user->forceFill(['last_login_at' => now()])->save();

        return [
            'token' => $token,
            'user' => $this->userPayload($user->fresh()),
        ];
    }

    public function me(Request $request)
    {
        return ['user' => $this->userPayload($request->attributes->get('auth_user'))];
    }

    public function logout(Request $request)
    {
        $apiKey = $request->attributes->get('auth_api_key');

        if ($apiKey && str_starts_with($apiKey->key_prefix, 'session_')) {
            $apiKey->delete();
        }

        return response()->noContent();
    }

    private function userPayload(Pengguna $user): array
    {
        return [
            'id' => $user->id,
            'nama' => $user->nama,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'can_write' => $user->canWrite(),
        ];
    }
}
