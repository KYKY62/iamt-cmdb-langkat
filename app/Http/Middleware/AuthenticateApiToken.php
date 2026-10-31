<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        // Hanya menerima: Authorization: Bearer <token>
        // (token sesi dari /auth/login atau API key permanen iamt_key_...).
        $token = $request->bearerToken();
        $token = is_string($token) ? trim($token) : null;

        if (! $token) {
            return response()->json([
                'message' => 'Unauthenticated. Kirim token melalui header Authorization: Bearer <token>.',
            ], 401, ['WWW-Authenticate' => 'Bearer']);
        }

        $tokenHash = hash('sha256', $token);

        $apiKey = ApiKey::query()
            ->where('key_hash', $tokenHash)
            ->with('user')
            ->first();

        $user = $apiKey?->user;

        if (! $user || $user->status !== 'aktif') {
            return response()->json([
                'message' => 'Unauthenticated. Token tidak valid, sudah dihapus, atau pemilik token tidak aktif.',
            ], 401, ['WWW-Authenticate' => 'Bearer error="invalid_token"']);
        }

        $apiKey->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('auth_user', $user);
        $request->attributes->set('auth_api_key', $apiKey);

        return $next($request);
    }
}
