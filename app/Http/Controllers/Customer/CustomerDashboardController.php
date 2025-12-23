<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerDashboardService;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    protected $service;

    public function __construct(CustomerDashboardService $service)
    {
        $this->service = $service;
    }

    public function dashboard(Request $request)
    {
        $user = auth()->user(); // Ambil user saat ini
        $task_id = $request->query('task_id') ?? null;

        $data = $this->service->dashboard($user, $task_id);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
