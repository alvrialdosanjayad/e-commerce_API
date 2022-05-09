<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Paroki extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'paroki';
    public $timestamps = false;

    protected $fillable = [
        'nama_paroki',
        'paroki_id_mysql'
    ];
}
