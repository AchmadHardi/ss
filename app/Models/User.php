<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'idmall__users';
    protected $primaryKey = 'user_id';

    public $timestamps = false; // karena kolom kamu bukan created_at / updated_at standar

    protected $fillable = [
        'email',
        'full_name',
        'password',
        'role',
        'is_email_verified',
        'reset_token',
        'reset_token_expired_at'
    ];

    protected $hidden = [
        'password',
        'reset_token'
    ];

    /* ================= JWT ================= */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
