<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Kategori extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'kategori';
    public $timestamps = false;

    protected $fillable = [
        'iconName',
        'iconText'
    ];
}
