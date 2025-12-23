<?php

namespace App\Services\Auth;

use App\Mail\VerifyEmailMail;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\ResetPasswordMail;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerAuthService
{
    /* =====================================================
     | LOGIN
     ===================================================== */
    public static function login(Request $request)
    {
        $email = $request->email;
        $password = $request->password;

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new HttpException(404, 'User tidak ditemukan');
        }

        if (!Hash::check($password, $user->password)) {
            throw new HttpException(403, 'Email atau password salah');
        }

        if ($user->is_email_verified != 1) {
            throw new HttpException(403, 'Silahkan verifikasi email terlebih dahulu');
        }

        $token = auth('api')->login($user);

        return response()->json([
            'status' => 'success',
            'meta' => [
                'origin' => 'IDMALL_CUSTOMER'
            ],
            'data' => [
                'user_id'   => $user->user_id,
                'email'     => $user->email,
                'full_name' => $user->full_name,
                'token'     => $token
            ]
        ]);
    }

    /* =====================================================
     | REGISTER
     ===================================================== */
    public static function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:idmall__users,email',
            'full_name' => 'required',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'email' => strtolower($request->email),
            'full_name' => $request->full_name, // ✅ BENAR
            'password' => Hash::make($request->password),
            'role' => 'CUSTOMER',
            'is_email_verified' => 0,
        ]);

        // generate token (mirip helper.generateEmailVerificationToken)
        $payload = [
            'email' => $user->email,
            'exp' => now()->addMinutes(60)->timestamp,
        ];

        $token = JWT::encode($payload, env('JWT_SECRET'), 'HS256');

        Mail::to($user->email)->send(
            new VerifyEmailMail($token)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil, silakan cek email untuk verifikasi',
            'meta' => [
                'user_id' => $user->user_id,
                'full_name' => $user->full_name
            ]
        ]);
    }


    /* =====================================================
     | SEND RESET PASSWORD EMAIL
     ===================================================== */
    public static function sendResetPassword(Request $request)
    {
        $request->validate([
            'target_email' => 'required|email'
        ]);

        $user = User::where('email', $request->target_email)->first();

        if (!$user) {
            throw new HttpException(404, 'Email tidak terdaftar');
        }

        $token = Str::random(64);

        $payload = [
            'email' => $user->email,
            'exp' => now()->addMinutes(30)->timestamp,
        ];

        $token = JWT::encode($payload, env('JWT_SECRET'), 'HS256');

        $link = url("/api/user/password/reset?token={$token}");

        $link = URL::to('/user/password/reset?token=' . $token);

        Mail::to($user->email)->send(
            new ResetPasswordMail($link)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Link reset password berhasil dikirim ke email'
        ]);
    }

    /* =====================================================
     | SUBMIT RESET PASSWORD
     ===================================================== */
    public static function submitResetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'new_password' => 'required|min:6'
        ]);

        try {
            $decoded = JWT::decode($request->token, new Key(env('JWT_SECRET'), 'HS256'));
        } catch (\Exception $e) {
            throw new HttpException(403, 'Token tidak valid atau sudah kadaluarsa');
        }

        $email = $decoded->email ?? null;
        if (!$email) {
            throw new HttpException(403, 'Token tidak valid');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new HttpException(404, 'User tidak ditemukan');
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah'
        ]);
    }
}
