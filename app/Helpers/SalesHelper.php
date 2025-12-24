<?php

namespace App\Helpers;

class SalesHelper
{
    /**
     * PORT dari getSalesPositionList (Express)
     */
    public static function getSalesPositionList(array $payload): array
    {
        $username = $payload['username'];
        $region   = $payload['region'] === 'ALL' ? '%' : $payload['region'];

        /**
         * NOTE:
         * getSalesPosition() diasumsikan SUDAH ADA
         * dan return struktur:
         * [
         *   'data' => [
         *      [
         *        'username' => '',
         *        'subordinate' => []
         *      ]
         *   ]
         * ]
         */
        $grouped = self::getSalesPosition([
            'username' => $username,
            'region'   => $region,
            'position' => $payload['position'] ?? null,
        ]);

        $allSubordinate = [];

        // ================= REGION ALL =================
        if ($region === '%') {
            $extractNames = function ($node) use (&$extractNames) {
                $names = [];

                if (!empty($node['username'])) {
                    $names[] = $node['username'];
                }

                if (!empty($node['subordinate'])) {
                    foreach ($node['subordinate'] as $sub) {
                        $names = array_merge($names, $extractNames($sub));
                    }
                }

                return $names;
            };

            foreach ($grouped['data'] as $node) {
                $allSubordinate = array_merge($allSubordinate, $extractNames($node));
            }
        }

        // ================= REGION SPESIFIK =================
        if ($region !== '%') {
            $collectNames = function ($data) use (&$collectNames) {
                $names = [];

                if (is_array($data) && isset($data[0])) {
                    foreach ($data as $item) {
                        $names = array_merge($names, $collectNames($item));
                    }
                } else {
                    $names[] = $data['username'];

                    if (!empty($data['subordinate'])) {
                        foreach ($data['subordinate'] as $sub) {
                            $names = array_merge($names, $collectNames($sub));
                        }
                    }
                }

                return $names;
            };

            $allSubordinate = $collectNames($grouped['data']);
        }

        return [
            'status' => 'success',
            'data'   => $allSubordinate,
        ];
    }

    /**
     * ⚠️ STUB – SESUAIKAN DENGAN LOGIC ASLI KAMU
     */
    private static function getSalesPosition(array $payload): array
    {
        // sementara dummy supaya tidak error
        return [
            'data' => [
                [
                    'username' => $payload['username'],
                    'subordinate' => [],
                ],
            ],
        ];
    }
}
