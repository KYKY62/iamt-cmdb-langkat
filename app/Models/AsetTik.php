<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetTik extends Model
{
    protected $table = 'aset_tik';

    // Tabel aset_tik tidak punya kolom created_at / updated_at.
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'kd_unit1' => 'integer',
        'kd_unit2' => 'integer',
        'total_barang' => 'integer',
        'total_harga_barang' => 'float',
    ];
}
