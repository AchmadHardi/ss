<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected TransactionService $service;

    public function __construct(TransactionService $service)
    {
        $this->service = $service;
    }

    public function submitted(Request $request, string $task_id)
    {
        $data = $this->service->getSubmittedTransaction($task_id);

        return response()->json($data, 200);
    }

    public function history(Request $request)
    {
        $data = $this->service->getHistory($request->all(), $request->user());
        return response()->json($data, 200);
    }
}
