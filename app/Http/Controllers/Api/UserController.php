<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return Pengguna::query()
            ->select(['id', 'nama', 'email', 'opd_id', 'role', 'status', 'last_login_at', 'created_at', 'updated_at'])
            ->with('opd:id,nama')
            ->orderBy('nama')
            ->get();
    }

    public function show(Pengguna $user)
    {
        return $user->load('opd:id,nama')->makeHidden(['password', 'api_token_hash']);
    }

    public function store(Request $request)
    {
        $data = $this->modelData($this->validated($request));
        $user = Pengguna::create($data);
        $this->audit('create', $user, null, $user->toArray(), $request);

        return response()->json($user->makeHidden(['password', 'api_token_hash']), 201);
    }

    public function update(Request $request, Pengguna $user)
    {
        $before = $user->toArray();
        $user->update($this->modelData($this->validated($request, $user)));
        $after = $user->fresh()->toArray();
        $this->audit('update', $user, $before, $after, $request);

        return $user->fresh()->makeHidden(['password', 'api_token_hash']);
    }

    public function destroy(Request $request, Pengguna $user)
    {
        if ($request->attributes->get('auth_user')?->id === $user->id) {
            return response()->json(['message' => 'User yang sedang login tidak dapat menghapus dirinya sendiri.'], 422);
        }

        $before = $user->toArray();
        $user->delete();
        $this->audit('delete', $user, $before, null, $request);

        return response()->noContent();
    }

    private function validated(Request $request, ?Pengguna $user = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('pengguna', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'opd_id' => ['nullable', 'uuid', 'exists:opd,id'],
            'role' => ['required', 'in:full,read_only,operator'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);
    }

    private function modelData(array $data): array
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            $data['api_token_hash'] = null;
        } else {
            unset($data['password']);
        }

        return $data;
    }

    private function audit(string $action, Pengguna $user, ?array $before, ?array $after, Request $request): void
    {
        AuditLog::create([
            'user_id' => $request->attributes->get('auth_user')?->id,
            'aksi' => $action,
            'tabel' => 'pengguna',
            'record_id' => $user->id,
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
