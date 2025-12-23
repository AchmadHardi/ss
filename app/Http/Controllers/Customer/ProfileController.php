<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;
use App\Exceptions\HttpException;

class ProfileController extends Controller
{
    // GET /profile
    public function getProfile(Request $request)
    {
        $tokenData = Helper::getAuthorizationTokenData($request);

        // Kalau payload token disimpan di key 'sub'
        $userId = $tokenData['sub'] ?? null;

        if (!$userId) {
            return response()->json([
                'message' => 'Invalid token payload'
            ], 401);
        }

        $profile = DB::table('idmall__users')
            ->where('user_id', $userId)
            ->first();

        if (!$profile) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'message' => 'success',
            'data' => [
                'profile' => $profile
            ],
            'meta' => new \stdClass()
        ]);
    }



    // GET /user/{user_id}/addresses
    public function getUserAddresses($user_id)
    {
        $addresses = DB::table('idmall__users_address')
            ->where('user_id', $user_id)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $addresses,
            'meta' => [
                'addresses' => count($addresses)
            ]
        ]);
    }

    // POST /user/{user_id}/address/add
    public function addNewAddress(Request $request, $user_id)
    {
        $body = $request->all();

        $count = DB::table('idmall__users_address')
            ->where('user_id', $user_id)
            ->count();

        if ($count >= 3) {
            return response()->json(['message' => 'Maksimum alamat yang diperbolehkan adalah 3!'], 403);
        }

        DB::table('idmall__users_address')->insert([
            'user_id' => $user_id,
            'address_type' => $body['address_type'],
            'address' => $body['address']
        ]);

        return response()->json([
            'status' => 'success',
            'meta' => new \stdClass()
        ], 201);
    }

    // PATCH /user/{user_id}/address/{address_id}/modify
    public function updateAddress(Request $request, $user_id, $address_id)
    {
        $body = $request->all();

        DB::table('idmall__users_address')
            ->where('user_id', $user_id)
            ->where('address_id', $address_id)
            ->update([
                'address_type' => $body['address_type'],
                'address' => $body['address']
            ]);

        return response()->json([
            'status' => 'success',
            'meta' => new \stdClass()
        ]);
    }

    // POST /user/delete
    public function deleteAccount(Request $request)
    {
        $body = $request->all();
        $email = $body['user_id'];

        DB::table('idmall__users')
            ->where('email', $email)
            ->update(['is_deleted' => 1]);

        return response()->json([
            'status' => 'success',
            'message' => 'Account deleted.'
        ]);
    }
}
