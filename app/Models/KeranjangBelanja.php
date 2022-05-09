<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class KeranjangBelanja extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'keranjang_belanja';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'lapak'
    ];
}
