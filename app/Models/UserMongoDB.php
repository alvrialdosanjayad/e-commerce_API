<?php

namespace App\Models;

use Jenssegers\Mongodb\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserMongoDB extends Authenticatable implements JWTSubject
{

    protected $connection = 'mongodb';
    protected $table = 'user';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'nomor_telepon',
        'alamat',
        'role',
        'client_id',
        'created_date'
    ];

    protected $hidden = [
        'password'
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * @return array
     */
    public function prepareCreateData($request)
    {
        return [
            'nama' => $request['nama'],
            'email' => $request['email'],
            'password' => $request['password'],
            'nomor_telepon' => $request['nomor_telepon'],
            'alamat' => '',
            'role' => 'user',
            'client_id' => $request['client_id'],
            'created_date' => date('d-m-Y'),
        ];
    }
}
