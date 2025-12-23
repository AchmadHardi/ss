<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;
use App\Exceptions\HttpException;
use Dayjs\Dayjs;

class DowngradeUpgradeController extends Controller
{
    // GET /customer/downgrade-upgrade/list
    public function list(Request $request)
    {
        $now = Helper::getLocalTime();
        return response()->json([
            'time_now' => $now,
            'data' => [],
        ]);
    }

    // POST /customer/request-du
    public function requestDU(Request $request)
{
    try {
        $session = Helper::getAuthorizationTokenData($request);
        $body = $request->all();

        // VALIDASI WAJIB
        if (!isset($body['task_id'], $body['new_monthly'])) {
            return response()->json([
                'message' => 'task_id and new_monthly are required'
            ], 422);
        }

        $existing = DB::table('tis_master.customer_activation')
            ->where('Task_ID', $body['task_id'])
            ->first();

        if (!$existing) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        // CEK DU BULAN INI
        $current_period = now()->format('Y-m');
        $du_count = DB::table('tis_master.customer_activation_du_log')
            ->where('Task_ID', $existing->Task_ID)
            ->where('Created_Date', 'like', "{$current_period}%")
            ->count();

        if ($du_count > 1 || $existing->Status !== 'ACTIVE') {
            return response()->json([
                'message' => 'Not allowed to perform DU'
            ], 403);
        }

        // INSERT DU LOG
        DB::table('tis_master.customer_activation_du_log')->insert([
            'Task_ID' => $body['task_id'],
            'DU_Date' => now()->format('Y-m-d'),
            'Old_Monthly' => $existing->Monthly_Price,
            'Old_Services' => $existing->Services,
            'New_Monthly' => $body['new_monthly'],
            'New_Services' => $body['new_services'] ?? null,
            'Created_By' => $session['email'],
            'Created_Date' => now()
        ]);

        // INSERT LOG CA (PASTIKAN SEMUA KOLOM NOT NULL TERISI)
        DB::table('tis_master.customer_activation_log')->insert([
            'Task_ID' => $existing->Task_ID,
            'Customer_ID' => $existing->Customer_ID,
            'Customer_Name' => $existing->Customer_Name,
            'Customer_Sub_Name' => $existing->Customer_Sub_Name,
            'Project_ID_By' => $session['email'] ?? 'system',
            'Project_ID_Date' => now(),
            'Created_Date' => now(),
            'Note' => $body['note'] ?? '[VIA IDMALL] update data',
            'Quotation_No_Installation' => $existing->Quotation_No_Installation ?? '',
            'Services' => $existing->Services ?? '',
            'Sub_Services' => $existing->Sub_Services ?? '',
            // Tambahkan kolom NOT NULL lain sesuai struktur DB
        ]);

        // UPDATE STATUS CUSTOMER ACTIVATION
        DB::table('tis_master.customer_activation')
            ->where('Task_ID', $body['task_id'])
            ->update(['Status' => 'DU']);

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully requesting Downgrade/Upgrade'
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}



    // POST /customer/sales/request-du
    public function salesRequestDU(Request $request)
    {
        // sama dengan requestDU, bisa beda created_by
        $session = Helper::getAuthorizationTokenData($request);
        $body = $request->all();

        // logic sama dengan requestDU
        return $this->requestDU($request);
    }

    // GET /customer/downgrade-upgrade/history
    public function history(Request $request)
    {
        $task_id = $request->query('task_id') ?? '%';
        $du_log_id = $request->query('du_log_id') ?? '%';
        $page = (int)($request->query('page') ?? 1);
        $per_page = (int)($request->query('per_page') ?? 10);
        $offset = ($page - 1) * $per_page;

        $data = DB::table('tis_master.customer_activation_du_progress')
            ->where('task_id', 'like', $task_id)
            ->where('du_log_id', 'like', $du_log_id)
            ->orderByDesc('ID')
            ->offset($offset)
            ->limit($per_page)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}
