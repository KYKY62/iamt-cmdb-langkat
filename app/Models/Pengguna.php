<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengguna extends Model
{
    use UsesUuid;

    protected $table = 'pengguna';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'opd_id',
        'role_legacy',
        'role',
        'status',
        'api_token_hash',
        'last_login_at',
    ];

    protected $hidden = ['password', 'api_token_hash'];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    public function canWrite(): bool
    {
        return $this->role === 'full' && $this->status === 'aktif';
    }

    /**
     * Modul yang boleh ditambah/diubah/dihapus per role (dipakai frontend).
     * ['*'] = semua modul. Harus sejalan dengan grup route di routes/api.php.
     */
    public const WRITABLE_MODULES = [
        'full' => ['*'],
        'operator' => ['applications', 'servers'],
        'read_only' => [],
    ];

    public function writableModules(): array
    {
        if ($this->status !== 'aktif') {
            return [];
        }

        return self::WRITABLE_MODULES[$this->role] ?? [];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }
}
