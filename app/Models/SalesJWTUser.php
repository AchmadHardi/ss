<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;

class SalesJWTUser implements JWTSubject
{
    protected $sales;

    public function __construct($sales)
    {
        $this->sales = $sales;
    }

    public function getJWTIdentifier()
    {
        return $this->sales->UserID;
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => 'sales',
            'apps_id' => 'IDMALL_SALES',
            'email' => $this->sales->Email,
            'name' => $this->sales->Name,
        ];
    }
}
