<?php

namespace App\Services\Customer;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PointService
{
    public function getTotalPoints($user)
    {
        $result = DB::selectOne(
            "
            SELECT iu.user_id, iu.email,
                   us.point_accumulated,
                   us.login_streak,
                   us.ads_watched
            FROM tis_master.idmall__users iu
            INNER JOIN idmall_mobile.users_statistics us
                ON us.user_id = iu.user_id
            WHERE iu.email = ?
            ",
            [$user->email]
        );

        return [
            'status' => 'SUCCESS',
            'data' => [[
                'total_point' => $result->point_accumulated ?? 0,
                'count_login_streak' => $result->login_streak ?? 0,
                'ads_watched_count' => $result->ads_watched ?? 0,
            ]]
        ];
    }

    public function rewardPoints(array $body, $user)
    {
        // user_id dari auth
        $user_id = $user->user_id ?? null;

        if (!$user_id) {
            return [
                'status' => 'FAILED',
                'message' => 'User tidak terautentikasi',
            ];
        }

        // Ambil parameter dari body
        $reward_id = $body['reward_id'] ?? null;
        $type      = $body['type'] ?? null;

        if (!$reward_id) {
            return [
                'status' => 'FAILED',
                'message' => 'Reward ID wajib diisi',
            ];
        }

        // Ambil info reward
        $rewardInfo = DB::selectOne(
            "SELECT point FROM tis_master.point_rewards WHERE id = ? LIMIT 1",
            [$reward_id]
        );

        if (!$rewardInfo) {
            return [
                'status' => 'FAILED',
                'message' => 'Reward tidak ditemukan',
            ];
        }

        // Isi pts berdasarkan reward info
        $pts = $rewardInfo->point;

        // Insert log transaksi reward
        DB::table('tis_master.user_reward_transaction_logs')->insert([
            'user_id'   => $user_id,
            'reward_id' => $reward_id,
            'pts'       => $pts,
            'type'      => $type,
        ]);

        return [
            'status' => 'success',
            'message' => "{$pts} point(s) added to account",
        ];
    }

    public function redeemPoints(array $body, $user)
    {
        $reward_id = $body['reward_id'] ?? null;
        $task_id   = $body['task_id'] ?? null;

        $email = $user->email ?? null;
        $user_id = $user->user_id ?? null;

        // 1) Validasi wajib
        if (!$email || !$user_id || !$reward_id || !$task_id) {
            throw new HttpException(400, "Parameter reward_id dan task_id wajib diisi");
        }

        // 2) Ambil user point
        $userRow = DB::select(
            "SELECT iu.user_id, iu.email, us.point_accumulated
         FROM tis_master.idmall__users iu
         INNER JOIN idmall_mobile.users_statistics us ON us.user_id = iu.user_id
         WHERE iu.email = ?",
            [$email]
        );

        if (empty($userRow)) {
            throw new HttpException(404, "Data tidak ditemukan");
        }

        $userStats = $userRow[0];

        // 3) Ambil info reward
        $checkPrize = DB::select(
            "SELECT id, point, cost, metadata, category
         FROM tis_master.point_rewards
         WHERE id = ?",
            [$reward_id]
        );

        if (empty($checkPrize)) {
            throw new HttpException(404, "Reward yang diinginkan tidak ditemukan");
        }

        $prize = $checkPrize[0];

        if ($userStats->point_accumulated < $prize->point) {
            throw new HttpException(403, "Tidak diizinkan, poin tidak cukup");
        }

        // 4) Ambil activation
        $activation = DB::select(
            "SELECT Task_ID, Customer_ID, Services, Sub_Product, Monthly_Price, Status, Region
         FROM tis_master.customer_activation
         WHERE Task_ID = ?",
            [$task_id]
        );

        if (empty($activation)) {
            throw new HttpException(404, "Data tidak ditemukan");
        }

        $act = $activation[0];

        // 5) dapat receivable
        $receivable = DB::select(
            "SELECT ID, AR_Val, AR_Remain, Inv_No, Inv_Date
         FROM tis_finance.account_receive
         WHERE Cust_ID = ? AND Status = 'CREATED'
         ORDER BY ID DESC LIMIT 1",
            [$act->Task_ID]
        );

        $now = Carbon::now('Asia/Jakarta');
        $start = $now->format('Y-m-d');
        $end = $now->copy()->addMonth()->format('Y-m-d');

        $meta = json_decode($prize->metadata, true) ?: [];

        // 6) Logic khusus kategori
        if ($prize->category === 'DISCOUNT') {

            $existingDiscount = DB::select(
                "SELECT * FROM tis_master.customer_activation_point_log
             WHERE Task_ID = ? AND Request_For = 'DISCOUNT'
             AND (Status IN ('CREATED','CLOSED') AND Downgrade_Original_Status='NOT_YET')",
                [$act->Task_ID]
            );

            if (!empty($existingDiscount)) {
                throw new HttpException(403, "Redeem sudah berhasil dilakukan");
            }

            if (empty($receivable)) {
                throw new HttpException(404, "Data tidak ditemukan");
            }

            $receivableData = $receivable[0];
            $final_price = $receivableData->AR_Remain;
            $discount = 0;
            $discount_note = "";

            if (($meta['type'] ?? '') === "percentage") {
                $discount = $receivableData->AR_Remain * (($meta['amount'] ?? 0) / 100);
                $final_price = ceil($receivableData->AR_Remain - $discount);
            } else if (($meta['type'] ?? '') === "flat") {
                $discount = ($meta['amount'] ?? 0);
                $final_price = ceil($receivableData->AR_Remain - $discount);
            }

            $discount_note = "Pelanggan CID : {$act->Task_ID} mendapatkan diskon sebesar {$meta['amount']} " .
                (($meta['type'] ?? '') === "percentage" ? "persen" : "rupiah") .
                ", harga lama : {$receivableData->AR_Remain}, harga setelah diskon: {$final_price} " .
                "menggunakan point sebanyak : {$prize->point} #";

            DB::insert(
                "INSERT INTO tis_master.customer_activation_point_log
             (Task_ID, Point_Value, Request_For, From_Data, To_Data,
              Start_Date, End_Date, Original_Data, Original_Monthly_Price,
              Created_By, Created_Date, Status)
             VALUES (?, ?, 'DISCOUNT', ?, ?, ?, ?, ?, ?, ?, ?, 'CREATED')",
                [
                    $act->Task_ID,
                    $prize->point,
                    $act->Monthly_Price,
                    $final_price,
                    $start,
                    $end,
                    $act->Services,
                    $act->Monthly_Price,
                    $user_id,
                    $now->format('Y-m-d H:i:s'),
                ]
            );

            DB::update(
                "UPDATE tis_finance.account_receive
             SET AR_Remain = ?, AR_Val = ?, Claim_Val = ?, Notes = CONCAT(COALESCE(Notes,''), ?),
                 Claim_Reference = ?
             WHERE ID = ?",
                [
                    $final_price,
                    $final_price,
                    $discount,
                    $discount_note,
                    "4-9006",
                    $receivableData->ID
                ]
            );
        }

        // 7) PRODUCT (mirip logic Express, jika perlu ditambah di sini)

        // 8) POINT — jika reward poin mentah
        if ($prize->category === "POINT") {
            DB::update(
                "UPDATE idmall_mobile.users_statistics
             SET point_accumulated = ?
             WHERE user_id = ?",
                [$userStats->point_accumulated + ($prize->point ?? 0), $userStats->user_id]
            );
        }

        // 9) Insert log transaksi
        $transactionId = Str::uuid()->toString();

        DB::insert(
            "INSERT INTO tis_master.user_reward_transaction_logs
         (transaction_id, user_id, reward_id, pts, type, source)
         VALUES (?, ?, ?, ?, ?, ?)",
            [
                $transactionId,
                $user_id,
                $prize->id,
                $prize->point,
                // gunakan tipe deduction constant atau 2
                2,
                "Redeem point."
            ]
        );

        DB::insert(
            "INSERT INTO tis_master.user_reward_details
         (transaction_id, status, description)
         VALUES (?, ?, ?)",
            [
                $transactionId,
                1,
                "Reward sedang diproses."
            ]
        );

        DB::update(
            "UPDATE idmall_mobile.users_statistics
         SET point_accumulated = ?
         WHERE user_id = ?",
            [$userStats->point_accumulated - ($prize->point ?? 0), $userStats->user_id]
        );

        return [
            'status' => 'SUCCESS',
            'message' => 'Berhasil mengclaim reward!'
        ];
    }

    public function getAvailableRewardsForPromo(array $qs)
    {
        $taskId = $qs['task_id'] ?? null;

        $response = [
            'status' => 'SUCCESS',
            'meta' => [
                'current_bandwidth' => 0,
            ],
            'data' => [],
        ];

        if (!$taskId) {
            throw new NotFoundHttpException('Nomor pelanggan wajib diisi!');
        }

        /**
         * 1. Check existing redeem / downgrade
         */
        $check = DB::select(
            "SELECT * FROM tis_master.customer_activation_point_log
             WHERE Task_ID = ?
             AND Request_For IN ('DISCOUNT', 'CHANGE_PRODUCT')
             AND (Status IN ('CREATED', 'CLOSED') AND Downgrade_Original_Status = 'NOT_YET')",
            [$taskId]
        );

        if (!empty($check)) {
            return $response;
        }

        /**
         * 2. Get customer activation
         */
        $ca = DB::select(
            "SELECT Task_ID, Status, Activation_Date, Services, Region,
                CASE
                    WHEN Activation_Date > '2025-10-19' THEN 'NEW'
                    ELSE 'EXISTING'
                END AS Customer_Type
            FROM tis_master.customer_activation
            WHERE Status = 'ACTIVE'
            AND Task_ID = ?
            LIMIT 1",
            [$taskId]
        );

        if (empty($ca)) {
            return $response;
        }

        /**
         * 3. Get product bandwidth
         */
        $product = DB::select(
            "SELECT Limitation FROM tis_master.produk WHERE Product_Code = ?",
            [$ca[0]->Services]
        );

        $bandwidth = $product[0]->Limitation ?? 0;

        /**
         * 4. Inject rewards based on bandwidth
         */
        $data = [];

        if (in_array($bandwidth, [20, 30])) {
            $lastSubscription = DB::select(
                "SELECT To_Data FROM tis_master.customer_activation_point_log
                 WHERE Task_ID = ?
                 ORDER BY ID DESC
                 LIMIT 1",
                [$taskId]
            );

            if (!empty($lastSubscription)) {
                $promo = DB::select(
                    "SELECT Redeem_Promo FROM tis_master.produk WHERE Product_Code = ?",
                    [$lastSubscription[0]->To_Data]
                );

                $promoCode = $promo[0]->Redeem_Promo ?? null;

                $promoReward = DB::select(
                    "SELECT id FROM tis_master.point_rewards
                     WHERE metadata LIKE ?",
                    ['%"redeem_promo_code": "' . $promoCode . '"%']
                );

                $rewardId = $promoReward[0]->id ?? null;

                $query = "
                    SELECT id, title, point AS point_cost, category, metadata
                    FROM tis_master.point_rewards
                    WHERE id IN (109,110,111,112,113,$rewardId)
                    AND show_on_list = 1
                ";
            } else {
                if ($ca[0]->Customer_Type === 'EXISTING') {
                    $query = "
                        SELECT id, title, point AS point_cost, category, metadata
                        FROM tis_master.point_rewards
                        WHERE id IN (109,110,111,112,113,117)
                        AND show_on_list = 1
                    ";
                } else {
                    $query = "
                        SELECT id, title, point AS point_cost, category, metadata
                        FROM tis_master.point_rewards
                        WHERE id IN (109,110,111,112,113,118,119)
                        AND show_on_list = 1
                    ";
                }
            }

            $data = array_merge($data, DB::select($query));
        }

        if (in_array($bandwidth, [50, 75, 100, 200])) {
            $rewardMap = [
                50  => [109, 110, 111, 112, 113, 114],
                75  => [109, 110, 111, 112, 113, 114],
                100 => [109, 110, 111, 112, 113, 115],
                200 => [109, 110, 111, 112, 113, 116],
            ];

            $ids = implode(',', $rewardMap[$bandwidth]);

            $q = DB::select("
                SELECT id, title, point AS point_cost, category, metadata
                FROM tis_master.point_rewards
                WHERE id IN ($ids)
                AND show_on_list = 1
            ");

            $data = array_merge($data, $q);
        }

        $response['meta']['current_bandwidth'] = $bandwidth;
        $response['data'] = $data;

        return $response;
    }
    public function rewardPointPerAdsWatched(array $body, $user)
    {
        // ambil user id
        $user_id = $user->user_id ?? null;

        if (!$user_id) {
            throw new HttpException(404, "User not found.");
        }

        // get current statistic
        $stats = DB::select(
            "SELECT iu.user_id, us.*
         FROM tis_master.idmall__users iu
         INNER JOIN idmall_mobile.users_statistics us ON iu.user_id = us.user_id
         WHERE iu.user_id = ?",
            [$user_id]
        );

        if (empty($stats)) {
            throw new HttpException(404, "User not found.");
        }

        $stat = $stats[0];

        // handle reset ads streak when not watched for 2 mins
        $now = Carbon::now('Asia/Jakarta');
        $last_watch = $stat->last_watched_ads_at
            ? Carbon::parse($stat->last_watched_ads_at)
            : $now;
        $difference = $now->diffInMinutes($last_watch);

        if ($difference >= 2) {
            DB::update(
                "UPDATE idmall_mobile.users_statistics
             SET ads_watched = 0
             WHERE user_id = ?",
                [$stat->user_id]
            );
            $stat->ads_watched = 0;
        }

        // handle watched ads type logic
        $ads_type = intval($body['ads_type'] ?? 0);
        $streak = $stat->ads_watched;
        $point_id_table = 26;

        if ($ads_type === 5 && $stat->ads_watched === 4) {
            $point_id_table = 15;
            $streak = 0;
        } elseif ($ads_type === 10 && $stat->ads_watched === 9) {
            $point_id_table = 16;
            $streak = 0;
        } elseif ($ads_type === 15 && $stat->ads_watched === 14) {
            $point_id_table = 17;
            $streak = 0;
        } else {
            $point_id_table = 26;
            $streak += 1;
        }

        // handle changed watch ads type
        if ($stat->watch_type_ads !== $ads_type) {
            $streak = 1;
            DB::update(
                "UPDATE idmall_mobile.users_statistics
             SET watch_type_ads = ?
             WHERE user_id = ?",
                [$ads_type, $stat->user_id]
            );
        }

        // get reward point definition
        $points = DB::select(
            "SELECT id, point, title FROM tis_master.point_rewards WHERE id = ?",
            [$point_id_table]
        );

        if (empty($points)) {
            throw new HttpException(404, "Reward point rule not found.");
        }

        $point_data = $points[0];

        // update statistics
        DB::update(
            "UPDATE idmall_mobile.users_statistics
         SET ads_watched = ?, point_accumulated = ?, last_watched_ads_at = ?
         WHERE user_id = ?",
            [
                $streak,
                $stat->point_accumulated + $point_data->point,
                $now->format('Y-m-d H:i:s'),
                $stat->user_id
            ]
        );

        // insert transaction log
        $transaction_id = Str::uuid()->toString();

        DB::insert(
            "INSERT INTO tis_master.user_reward_transaction_logs
         (transaction_id, user_id, reward_id, pts, type, source, data)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $transaction_id,
                $stat->user_id,
                $point_data->id,
                $point_data->point,
                1, // sesuai dengan POINT_REWARD.TRANSACTION.ADDITION
                $point_data->title,
                json_encode(['watched_at' => $now->format('Y-m-d H:i:s')])
            ]
        );

        return [
            'status' => 'SUCCESS',
            'message' => 'Berhasil menonton iklan.',
            'data' => [
                'reward_point' => $point_data->point,
            ],
        ];
    }

    public function rewardPointPerPresence(array $body, $user)
    {
        $email = $user->email ?? null;
        $user_id = $user->user_id ?? null;

        if (!$email || !$user_id) {
            throw new HttpException(404, "User not found");
        }

        // get last access & stats
        $lastAccess = DB::select(
            "SELECT iu.user_id, iu.email, us.last_access, us.login_streak, us.ads_watched, us.point_accumulated
         FROM tis_master.idmall__users iu
         INNER JOIN idmall_mobile.users_statistics us ON us.user_id = iu.user_id
         WHERE iu.email = ?",
            [$email]
        );

        if (empty($lastAccess)) {
            throw new HttpException(404, "User not found.");
        }

        $stat = $lastAccess[0];

        // waktu sekarang
        $now = Carbon::now('Asia/Jakarta');
        $nowDate = $now->format('Y-m-d');

        $lastTime = $stat->last_access
            ? Carbon::parse($stat->last_access)
            : null;

        // apakah sudah presensi hari ini?
        $isToday = $lastTime ? $now->isSameDay($lastTime) : false; // cek date sama :contentReference[oaicite:0]{index=0}

        // jika sudah dilakukan
        if ($isToday && $stat->last_access !== null) {
            return [
                'status' => 'SUCCESS',
                'message' => "Presensi sudah dilakukan.",
            ];
        }

        $loginStreak = $stat->login_streak;

        // cek kehilangan streak
        $onLosingStreak = $lastTime
            ? $now->diffInDays($lastTime) > 1
            : false;

        $id_of_point = 10; // default

        // logic cari id_of_point sesuai streak
        if ($loginStreak === 6) {
            $id_of_point = $onLosingStreak ? 10 : 11;
        } elseif ($loginStreak === 13) {
            $id_of_point = $onLosingStreak ? 10 : 12;
        } elseif ($loginStreak === 20) {
            $id_of_point = $onLosingStreak ? 10 : 13;
        } elseif ($loginStreak >= 27) {
            if ((($loginStreak + 1) % 7) === 0) {
                $id_of_point = $onLosingStreak ? 10 : 14;
            } else {
                $id_of_point = $onLosingStreak ? 10 : 10;
            }
        } else {
            $id_of_point = 10;
        }

        // ambil poin reward
        $points = DB::select(
            "SELECT * FROM tis_master.point_rewards WHERE id = ?",
            [$id_of_point]
        );

        if (empty($points)) {
            throw new HttpException(404, "Point reward not found.");
        }

        $pointData = $points[0];

        // update statistik
        $newPointAccumulated = $stat->point_accumulated + $pointData->point;
        $newLoginStreak = $onLosingStreak ? 1 : ($stat->login_streak + 1);

        DB::update(
            "UPDATE idmall_mobile.users_statistics
         SET point_accumulated = ?, login_streak = ?, last_access = ?
         WHERE user_id = ?",
            [
                $newPointAccumulated,
                $newLoginStreak,
                $now->format('Y-m-d H:i:s'),
                $user_id
            ]
        );

        // insert log transaksi
        $transactionId = Str::uuid()->toString();

        DB::insert(
            "INSERT INTO tis_master.user_reward_transaction_logs
         (transaction_id, user_id, reward_id, pts, type, source, data)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $transactionId,
                $user_id,
                $pointData->id,
                $pointData->point,
                1,
                $pointData->title,
                null
            ]
        );

        return [
            'status' => 'SUCCESS',
            'message' => 'Anda telah berhasil melakukan presensi hari ini.',
        ];
    }

    public function getHistoryPoints($user)
    {
        $data = [];

        // Pastikan user tersedia
        if (!$user || !isset($user->user_id)) {
            return [
                'status' => 'FAILED',
                'message' => 'User tidak ditemukan',
                'meta' => ['count_data' => 0],
                'data' => []
            ];
        }

        // Query history POINT
        $histories = DB::select(
            "
            SELECT
                urtl.id,
                urtl.source,
                urtl.data,
                urtl.transaction_id,
                urtl.user_id,
                pr.title,
                urtl.pts,
                urtl.created_at,
                urtl.updated_at,
                CASE
                    WHEN urtl.type = 1 THEN 'ADDITION'
                    WHEN urtl.type = 2 THEN 'DEDUCTION'
                END as type
            FROM tis_master.user_reward_transaction_logs urtl
            INNER JOIN tis_master.point_rewards pr ON pr.id = urtl.reward_id
            WHERE urtl.user_id = ?
            ORDER BY urtl.created_at DESC
            ",
            [$user->user_id]
        );

        // Mapping results to struktur response
        foreach ($histories as $element) {
            $data[] = [
                'transaction_id' => $element->transaction_id,
                'type'           => $element->type,
                'amount'         => $element->pts,
                'source'         => $element->source,
                'timestamp'      => $element->created_at,
                'data'           => $element->data,
            ];
        }

        return [
            'status' => 'SUCCESS',
            'message' => 'Point transaction histories.',
            'meta' => [
                'count_data' => count($data),
            ],
            'data' => $data,
        ];
    }
}
