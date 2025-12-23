<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function paymentMethod(Request $request)
    {
        return response()->json(
            app(PaymentService::class)->getAvailablePaymentMethod($request)
        );
    }

    public function paymentPage(Request $request)
    {
        if ($request->query('status') === 'unavailable') {
            return response()->file(
                public_path('assets/payment/payment-unavailable.html')
            );
        }

        return response()->json([
            'message' => 'OK'
        ]);
    }
}
