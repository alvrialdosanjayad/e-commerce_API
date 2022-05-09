<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Transaksi extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'transaksi';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'lapak_id',
        'nama_lapak',
        'produk',
        'status',
        'expired_data',
        'updated_date',
        'created_date'
    ];
}
