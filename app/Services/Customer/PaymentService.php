<?php

namespace App\Services\Customer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class PaymentService
{
    protected function xenditLogin()
    {
        $response = Http::post(
            config('services.xendit.base_url') . '/Login',
            [
                'UserName' => config('services.xendit.username'),
                'Password' => config('services.xendit.password'),
            ]
        );

        if (!$response->ok() || $response['code'] == 0) {
            abort(500, 'Error remote gateway: [AU]');
        }

        return $response->json();
    }

    protected function getToken()
    {
        $session = DB::table('tis_master.idmall__users_session')
            ->where('token_name', 'xendit')
            ->where('expired_at', '>', now())
            ->first();

        if ($session) {
            return $session->token;
        }

        $login = $this->xenditLogin();
        $expiredAt = Carbon::createFromTimestamp($login['token']['exp'])
            ->addDay()
            ->format('Y-m-d H:i:s');

        DB::table('tis_master.idmall__users_session')
            ->updateOrInsert(
                ['token_name' => 'xendit'],
                [
                    'token' => $login['token']['token'],
                    'expired_at' => $expiredAt,
                ]
            );

        return $login['token']['token'];
    }
protected function getBanks()
{
    $response = Http::withBasicAuth(
        env('XENDIT_API_KEY'),
        '' // password kosong
    )
    ->get('https://api.xendit.co/v2/disbursements/banks'); // atau endpoint yang sesuai

    if (!$response->ok()) {
        abort(500, 'Failed to fetch bank list');
    }

    return $response->json();
}
    public function getAvailablePaymentMethod($request)
    {
        $banks = $this->getBanks();

        $images = DB::table('idmall__payment_method')
            ->select('code', 'img_path')
            ->get();

        $host = $request->getSchemeAndHttpHost();
        $iconBase = $host . '/assets/payment';

        $bankResult = [];

        foreach ($banks as $bank) {
            foreach ($images as $img) {
                if ($bank['code'] === $img->code) {
                    $bankResult[] = array_merge($bank, [
                        'icon_url' => $iconBase . '/' . $img->img_path
                    ]);
                }
            }
        }

        $taskId = $request->input('task_id');

        return [
            'message' => 'success',
            'meta' => [],
            'data' => [
                'tis_account' => [
                    [
                        'name' => 'BCA',
                        'account_number' => '13715' . $taskId,
                    ],
                    [
                        'name' => 'BRI',
                        'account_number' => '14947' . $taskId,
                    ],
                ],
                'bank' => $bankResult,
            ],
        ];
    }
}
