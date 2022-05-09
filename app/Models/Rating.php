<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Rating extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'rating';
    public $timestamps = false;

    protected $fillable = [
        'transaksi_id',
        'produk_id',
        'gambar_rating',
        'user',
        'value',
        'catatan',
        'status_rating',
        'updated_date',
        'created_date'
    ];
}
