<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailAsetTik extends Model
{
    protected $table = 'detail_aset_tik';

    // Primary key Code_ID berupa UUID (CHAR 36), bukan auto increment.
    protected $primaryKey = 'Code_ID';

    public $incrementing = false;

    protected $keyType = 'string';

    // Tabel detail_aset_tik tidak punya kolom created_at / updated_at.
    public $timestamps = false;

    protected $fillable = [
        'Code_ID',
        'Nm_Aset5',
        'Nm_Unit',
        'Kd_Bidang',
        'Kd_Unit',
        'Merk',
        'Type',
        'Keterangan',
        'lastKondisi',
    ];

    protected $casts = [
        'Kd_Bidang' => 'integer',
        'Kd_Unit' => 'integer',
    ];
}
