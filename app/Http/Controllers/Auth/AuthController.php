<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Auth\CustomerAuthService;
use App\Services\Auth\SalesAuthService;
use App\Services\Auth\TechnicianAuthService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;
use Firebase\JWT\JWT;


class AuthController extends Controller
{
    /* ======================================
     | LOGIN (SUDAH BENAR)
     ====================================== */
    public function login(Request $request)
    {
        $appsId = $request->query('apps_id');

        if (!$appsId) {
            throw new HttpException(404, 'Apps id not found.');
        }

        return match ($appsId) {
            'IDMALL_CUSTOMER'   => CustomerAuthService::login($request),
            'IDMALL_SALES'      => SalesAuthService::login($request),
            'IDMALL_TECHNICIAN' => TechnicianAuthService::login($request),
            default => throw new HttpException(403, 'Apps id tidak dikenali'),
        };
    }

    /* ======================================
     | REGISTER (CUSTOMER)
     ====================================== */
    public function register(Request $request)
    {
        return CustomerAuthService::register($request);
    }

    /* ======================================
     | SEND RESET PASSWORD EMAIL
     ====================================== */
    public function sendResetPassword(Request $request)
    {
        return CustomerAuthService::sendResetPassword($request);
    }

    public function showResetForm(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            abort(403, 'Token dibutuhkan');
        }

        return view('auth.reset-password', compact('token'));
    }

    public function submitResetPassword(Request $request)
    {
        return CustomerAuthService::submitResetPassword($request);
    }


    /* ======================================
     | LOGOUT
     ====================================== */
    public function logout(Request $request)
    {
        auth()->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout'
        ]);
    }

    /* ======================================
     | CHECK AUTH
     ====================================== */
    public function checkAuth()
    {
        return response()->json([
            'status' => 'success',
            'data' => auth()->user()
        ]);
    }
}
