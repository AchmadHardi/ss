<?php

namespace App\Services\Customer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
class TransactionService
{
    public function getSubmittedTransaction(string $task_id): array
    {
        $task_id = trim($task_id);

        /** ===============================
         * QUERY VA
         * =============================== */
        $select_va = "
            SELECT
                bank_code,
                account_number,
                expected_amount,
                Task_ID
            FROM idmall__xendit_va_created_callback
            WHERE Task_ID = ?
        ";

        /** ===============================
         * QUERY OT
         * =============================== */
        $select_ot = "
            SELECT
                retail_outlet_name,
                payment_code,
                expected_amount,
                Task_ID
            FROM idmall__xendit_ot_created_callback
            WHERE Task_ID = ?
        ";

        $va = DB::select($select_va, [$task_id]);
        $ot = DB::select($select_ot, [$task_id]);

        return [
            'status'  => 'success',
            'message' => 'berhasil mengambil transaksi',
            'data'    => [
                'ot' => $this->emptyOrRows($ot),
                'va' => $this->emptyOrRows($va),
            ],
        ];
    }

    /**
     * Mimic helper.emptyOrRows() dari Express
     */
    private function emptyOrRows($rows): array
    {
        return empty($rows) ? [] : $rows;
    }

    public function getHistory(array $body, $user): array
    {
        $task_id = $body['task_id'] ?? null;

        if (!$task_id) {
            return [
                'status'  => 'error',
                'message' => 'task_id wajib diisi',
                'data'    => [],
            ];
        }

        $hasil = [];

        /** ===============================
         * CUSTOMER ACTIVATION
         * =============================== */
        $rCA = DB::select(
            "SELECT * FROM tis_master.customer_activation WHERE Task_ID = ?",
            [$task_id]
        );

        $dataFromCA = $rCA[0]->data_from ?? null;

        /** ===============================
         * NOTES
         * =============================== */
        $notes = DB::select(
            "SELECT *
            FROM tis_master.customer_activation_notes
            WHERE Task_ID = ?
            ORDER BY ID ASC",
            [$task_id]
        );

        if (empty($notes)) {
            return [
                'status'  => 'success',
                'message' => 'Tidak ada data',
                'data'    => [],
            ];
        }

        /** ===============================
         * IDMALL
         * =============================== */
        $rIdmall = DB::select(
            "SELECT *
            FROM tis_master.idmall__customer_activation
            WHERE data_from = ?",
            [$task_id]
        );

        if (empty($rIdmall) && empty($notes)) {
            $rIdmallCa = DB::select(
                "SELECT *
                FROM tis_master.idmall__customer_activation
                WHERE Task_ID = ?",
                [$task_id]
            );

            if (!empty($rIdmallCa) && $rIdmallCa[0]->Status !== 'CANCELED') {
                return [
                    'message' => 'Tidak ada data',
                    'data' => [
                        [
                            'status_from' => '',
                            'status'      => 'CREATED_IDMALL',
                            'date'        => $rIdmallCa[0]->Created_Date,
                            'note'        => '',
                        ],
                    ],
                ];
            }
        }

        if (!empty($notes) && !empty($rIdmall)) {
            $hasil[] = [
                'status_from' => '',
                'status'      => 'CREATED_IDMALL',
                'date'        => $rIdmall[0]->Created_Date ?? null,
                'note'        => '',
            ];
        }

        /** ===============================
         * LOOP NOTES
         * =============================== */
        foreach ($notes as $note) {
            $status      = $note->Status;
            $status_from = $note->Status_From;

            if ($status === 'CREATED' && $dataFromCA === 'MOBILE') {
                $status_from = 'CREATED_IDMALL';
            }

            $hasil[] = [
                'status_from' => $status_from,
                'status'      => $status,
                'note'        => $note->Note,
                'date'        => $note->Created_Date,
            ];
        }

        /** ===============================
         * MAPPING STATUS (CONFIG)
         * =============================== */
        $mapStatus = config('db_config.OSS_PROCESS_MSGS_TO_CUSTOMER', []);

        foreach ($hasil as &$item) {
            if (isset($mapStatus[$item['status']])) {
                $item['status'] = $mapStatus[$item['status']];
            }
        }
        unset($item);

        /** ===============================
         * UNIQUE STATUS (LATEST DATE)
         * =============================== */
        $unique = [];

        foreach ($hasil as $row) {
            $key = $row['status'];

            if (
                !isset($unique[$key]) ||
                strtotime($row['date']) > strtotime($unique[$key]['date'])
            ) {
                $unique[$key] = $row;
            }
        }

        return [
            'status'  => 'success',
            'message' => 'success',
            'data'    => array_values($unique),
        ];
    }
}
