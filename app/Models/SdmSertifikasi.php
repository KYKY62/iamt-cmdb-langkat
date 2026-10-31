<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdmSertifikasi extends Model
{
    use UsesUuid;

    protected $table = 'sdm_sertifikasi';

    protected $fillable = ['sdm_id', 'path', 'original_name', 'size_bytes'];

    // Lokasi file di server tidak perlu dikirim ke frontend.
    protected $hidden = ['path'];

    protected $casts = ['size_bytes' => 'integer'];

    public function sdm(): BelongsTo
    {
        return $this->belongsTo(Sdm::class, 'sdm_id');
    }
}
