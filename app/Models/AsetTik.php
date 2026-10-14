<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsetTik extends Model
{
    protected $table = 'aset_tik';

    protected $guarded = ['id'];

    protected $casts = [
        'total_barang' => 'integer',
        'total_harga_barang' => 'float',
    ];
}
