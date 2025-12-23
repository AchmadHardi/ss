<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\SalesDashboardService;

class SalesDashboardController extends Controller
{
    public function dashboard()
    {
        return response()->json(
            SalesDashboardService::dashboard(auth()->user())
        );
    }
}
