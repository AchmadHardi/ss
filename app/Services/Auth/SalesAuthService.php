<?php

namespace App\Services\Auth;

use App\Models\SalesJWTUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Facades\JWTAuth;

class SalesAuthService
{
    public static function login(Request $request)
    {
        $userId = $request->input('user_id');
        $password = $request->input('password');

        $sales = DB::table('tis_main.user_l')
            ->where('UserID', $userId)
            ->where('Status', 'ACTIVE')
            ->first();

        if (!$sales) {
            throw new HttpException(403, 'Akun sales tidak terdaftar');
        }

        // contoh bypass password legacy
        if ($password !== 'tis009') {
            throw new HttpException(403, 'Password salah');
        }

        $token = JWTAuth::fromUser(
            new \App\Models\SalesJWTUser($sales)
        );

        return response()->json([
            'status' => 'success',
            'meta' => [
                'origin' => 'IDMALL_SALES'
            ],
            'data' => [
                'user_id' => $sales->UserID,
                'name' => $sales->Name,
                'email' => $sales->Email,
                'token' => $token
            ]
        ]);
    }

}
