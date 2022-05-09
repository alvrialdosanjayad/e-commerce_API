<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class Lapak extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'lapak';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'nama_lapak',
        'paroki_lapak',
        'deskripsi_lapak',
        'alamat_lapak',
        'no_telepon_lapak',
        'produk_lapak',
        'status_lapak',
        'catatan_lapak',
        'created_date',
        'updated_date'
    ];
}
