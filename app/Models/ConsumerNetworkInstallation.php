<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumerNetworkInstallation extends Model
{
    use UsesUuid;

    protected $table = 'consumer_network_installations';

    protected $fillable = [
        'site_id',
        'device_id',
        'replaced_by_device_id',
        'role',
        'status',
        'reported_at',
        'installed_at',
        'removed_at',
        'installed_by',
        'notes',
    ];

    // Tanggal + jam. Format tetap supaya API mengirim waktu lokal (Asia/Jakarta),
    // contoh: "2026-10-27 14:30:00", bukan ISO UTC.
    protected $casts = [
        'reported_at' => 'datetime:Y-m-d H:i:s',
        'installed_at' => 'datetime:Y-m-d H:i:s',
        'removed_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(ConsumerNetworkSite::class, 'site_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(ConsumerNetworkDevice::class, 'device_id');
    }

    public function replacementDevice(): BelongsTo
    {
        return $this->belongsTo(ConsumerNetworkDevice::class, 'replaced_by_device_id');
    }
}
