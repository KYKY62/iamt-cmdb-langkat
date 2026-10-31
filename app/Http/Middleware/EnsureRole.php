<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Batasi route untuk role tertentu.
 * Contoh: ->middleware('role:full,operator')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->attributes->get('auth_user');

        if (! $user || $user->status !== 'aktif' || ! in_array($user->role, $roles, true)) {
            $role = $user?->role ?? 'tamu';

            return response()->json([
                'message' => "Role {$role} tidak memiliki akses ke endpoint ini.",
            ], 403);
        }

        return $next($request);
    }
}
