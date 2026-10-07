<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-API-Key') ?: $request->header('Api-Key');

        if (! $token) {
            $authorization = trim((string) $request->header('Authorization'));

            if (preg_match('/^(?:Bearer|ApiKey|Api-Key)\s+(.+)$/i', $authorization, $matches)) {
                $token = $matches[1];
            } elseif (str_starts_with($authorization, 'iamt_key_')) {
                // Mendukung Postman API Key dengan key header "Authorization".
                $token = $authorization;
            }
        }

        $token = is_string($token) ? trim($token) : null;

        if (! $token) {
            return response()->json([
                'message' => 'Unauthenticated. Kirim API key melalui header Authorization: Bearer <api-key> atau X-API-Key.',
            ], 401);
        }

        $tokenHash = hash('sha256', $token);

        $apiKey = ApiKey::query()
            ->where('key_hash', $tokenHash)
            ->with('user')
            ->first();

        $user = $apiKey?->user;

        if (! $user || $user->status !== 'aktif') {
            return response()->json([
                'message' => 'Unauthenticated. API key tidak valid, sudah dihapus, atau pemilik key tidak aktif.',
            ], 401);
        }

        $apiKey->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('auth_user', $user);
        $request->attributes->set('auth_api_key', $apiKey);

        return $next($request);
    }
}
