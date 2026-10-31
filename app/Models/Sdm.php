<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sdm extends Model
{
    use UsesUuid;

    protected $table = 'sdm';

    public const PENDIDIKAN = ['SD', 'SMP', 'SMA', 'S1', 'S2', 'S3'];

    protected $fillable = [
        'opd_id',
        'nama',
        'jabatan',
        'pendidikan',
    ];

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class, 'opd_id');
    }

    /**
     * Sertifikasi (PDF) milik SDM ini, bisa lebih dari satu.
     */
    public function sertifikasi(): HasMany
    {
        return $this->hasMany(SdmSertifikasi::class, 'sdm_id')->orderBy('created_at');
    }
}
