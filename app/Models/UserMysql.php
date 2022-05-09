<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMysql extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'client_id',
        'nik',
        'nomor_telepon',
        'verification_token',
        'email_verified_at'
    ];

    protected $hidden = [
        'password'
    ];
}
