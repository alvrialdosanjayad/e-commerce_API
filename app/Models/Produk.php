<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Produk extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'produk';
    public $timestamps = false;

    protected $fillable = [
        'lapak_produk',
        'kategori_produk',
        'nama_produk',
        'gambar_produk',
        'deskripsi_produk',
        'berat_produk',
        'harga_produk',
        'stok_produk',
        'kondisi_produk',
        'keamanan_produk',
        'merek_produk',
        'variasi_produk',
        'penjualan_produk',
        'rating_produk',
        'deleted_date',
        'created_date',
        'updated_date'
    ];
}
