<?php

namespace App\Services\Customer;

use DB; // gunakan query builder atau raw query

class CustomerDashboardService
{
    public function dashboard($user, $task_id = null)
    {
        $data = [];

        // Ambil user_id dari email
        $user_record = DB::table('tis_master.idmall__users')
            ->where('email', $user->email)
            ->first();

        if (!$user_record) {
            return $data;
        }

        $user_id = $user_record->user_id;

        // Ambil activation
        $activation = DB::table('idmall_mobile.users_to_activation as uta')
            ->select(
                'uta.*',
                DB::raw('(SELECT Task_ID FROM tis_master.idmall__customer_activation WHERE ID = uta.leads_id) as registration_task_id'),
                DB::raw('(SELECT Task_ID FROM tis_master.customer_activation WHERE ID = uta.activation_id) as activation_task_id')
            )
            ->where(function ($query) use ($task_id) {
                $query->where('leads_id', function($q) use ($task_id) {
                    $q->select('ID')->from('tis_master.idmall__customer_activation')->where('Task_ID', $task_id);
                })
                ->orWhere('activation_id', function($q) use ($task_id) {
                    $q->select('ID')->from('tis_master.customer_activation')->where('Task_ID', $task_id);
                });
            })
            ->where('user_id', $user_id)
            ->first();

        if (!$activation) {
            return $data;
        }

        $activation_task_id = $activation->activation_task_id ?? null;
        $registration_task_id = $activation->registration_task_id ?? null;

        // Pilih table
        $table_name = $activation_task_id ? 'customer_activation' : 'idmall__customer_activation';
        $task_to_query = $activation_task_id ?? $registration_task_id;

        // Ambil detail customer activation
        $res = DB::table("tis_master.$table_name as ca")
            ->leftJoin(DB::raw("(SELECT ar1.*
                FROM tis_finance.account_receive ar1
                JOIN (SELECT Cust_ID, MAX(Inv_Date) as MaxInvDate
                    FROM tis_finance.account_receive
                    GROUP BY Cust_ID) ar2
                ON ar1.Cust_ID = ar2.Cust_ID AND ar1.Inv_Date = ar2.MaxInvDate
            ) as ar"), 'ar.Cust_ID', '=', 'ca.Task_ID')
            ->leftJoin('radiusdb.radacct as rad', function($join) {
                $join->on('rad.username', '=', 'ca.External_ID')
                     ->whereRaw('MONTH(rad.acctstarttime) = MONTH(CURRENT_DATE())');
            })
            ->leftJoin('tis_master.user_reward_transaction_logs as rt', 'rt.user_id', '=', 'ca.Email_Customer')
            ->selectRaw("
                ca.ID, ca.Task_ID, ca.Customer_Sub_Name as Customer_Name,
                ca.Services as Product_Code, ca.Sub_Product as Product_Name, ca.Status,
                CASE
                    WHEN ar.AR_Remain > 0 THEN 'Tagihan'
                    WHEN ar.AR_Remain = 0 THEN 'Terbayar'
                    ELSE 'Tidak ada tagihan'
                END as Bill_Status,
                '' as Notification_Message,
                IFNULL(ar.Due_Date, CURDATE()) as Due_Date,
                IFNULL(ar.Inv_Date, CURDATE()) as Inv_Date,
                IFNULL(ar.Payment, 0) as Total_Payment,
                IFNULL(ar.AR_Remain, 0) as AR_Remain,
                IFNULL(ar.AR_Paid, 0) as AR_Paid,
                IFNULL(DATE_FORMAT(rad.acctstarttime, '%Y-%m'), DATE_FORMAT(NOW(), '%Y-%m')) as Period,
                IFNULL(rt.pts, 0) as Points,
                IFNULL(SUM(rad.acctinputoctets)/1000000000, 0) as GB_in
            ")
            ->where('ca.Task_ID', $task_to_query)
            ->groupBy('ca.ID')
            ->orderByDesc('ca.ID')
            ->get();

        // Ambil notes terbaru
        $notes = DB::table('tis_master.customer_activation_notes as can')
            ->select('can.Notification_Message_ID', 'can.Created_Date')
            ->where('can.Task_ID', $task_to_query)
            ->orderByDesc('can.Created_Date')
            ->first();

        if ($notes) {
            $message = DB::table('tis_master.master_notification')
                ->where('ID', $notes->Notification_Message_ID)
                ->value('Body');
            if ($message && isset($res[0])) {
                $res[0]->Notification_Message = $message;
            }
        }

        return isset($res[0]) ? (array)$res[0] : [];
    }
}
