<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class EmailVerificationController extends Controller
{
    public function verify($token)
    {
        try {
            $decoded = JWT::decode(
                $token,
                new Key(env('JWT_SECRET'), 'HS256')
            );

            if ($decoded->exp < time()) {
                return response()->json([
                    'message' => 'Token expired'
                ], 403);
            }

            $user = User::where('email', $decoded->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            if ($user->is_email_verified == 1) {
                return response()->json([
                    'message' => 'Email sudah diverifikasi'
                ]);
            }

            $user->update([
                'is_email_verified' => 1,
                'email_verified_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Email berhasil diverifikasi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token tidak valid'
            ], 403);
        }
    }
}
