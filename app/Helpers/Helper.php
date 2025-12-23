<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

class Helper
{
    // ===================== TIME =====================
    public static function getLocalTime()
    {
        return Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
    }

    public static function getYYYYMMDD()
    {
        return Carbon::now('Asia/Jakarta')->format('Y-m-d');
    }

    public static function generateDatabaseTimeStamp()
    {
        return Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
    }

    // ===================== TOKEN =====================
    public static function generateToken($payload, $secret = null, $expireIn = '+1 day')
    {
        $secret = $secret ?: env('JWT_SECRET', 'your-secret-key');

        // 🔥 FIX UTAMA
        if (is_object($payload)) {
            $payload = [
                'user_id' => $payload->user_id ?? null,
                'email'   => $payload->email ?? null,
                'role'    => $payload->role ?? null,
            ];
        }

        if (!is_array($payload)) {
            throw new \Exception('JWT payload must be array');
        }

        $payload['exp'] = strtotime($expireIn);

        return JWT::encode($payload, $secret, 'HS256');
    }


    // ===================== UTIL =====================
    public static function emptyOrRows($rows)
    {
        return $rows ?? [];
    }

    public static function getOffset($current_page = 1, $list_per_page = 10)
    {
        return ($current_page - 1) * $list_per_page;
    }

    public static function createTaskID($existing = null)
    {
        $new_task_id = ($existing !== null) ? ((int)$existing + 1) : 1;
        return (string)$new_task_id;
    }

    public static function createExternalID()
    {
        $date = new \DateTime();
        $utc = $date->format('c'); // ISO string
        $year = $date->format('y'); // last 2 digits
        $month = $date->format('m'); // 2 digit
        $suffix = "{$year}{$month}";
        $hash = md5($utc);
        $prefix = substr($hash, 0, 5);
        return "{$prefix}/SR/{$suffix}";
    }

    public static function convertPhoneNumber($phone)
    {
        if (!$phone) return [''];
        $numbers = explode(',', $phone);
        $result = [];
        foreach ($numbers as $num) {
            $num = preg_replace('/[\s+\-]/', '', $num);
            if (str_starts_with($num, '0')) {
                $num = '62' . substr($num, 1);
            }
            $result[] = $num;
        }
        return $result;
    }

    // ===================== COVERAGE =====================
    public static function degreeToRadians($deg)
    {
        return ($deg * pi()) / 180.0;
    }

    public static function calculateBoundingArea($latitude, $longitude, $radius = 0.5)
    {
        $km_scale = 0.008983112; // 1km in degrees
        $lng_ratio = 1 / cos(self::degreeToRadians($latitude));
        $north = $latitude + $radius * $km_scale;
        $south = $latitude - $radius * $km_scale;
        $west  = $longitude - $radius * $km_scale * $lng_ratio;
        $east  = $longitude + $radius * $km_scale * $lng_ratio;
        return compact('north', 'south', 'east', 'west');
    }

    public static function isLatLonInsideCoverage($lat, $lon, $radiusKm = 20)
    {
        $lat = (float) $lat;
        $lon = (float) $lon;
        $radiusMeters = $radiusKm * 1000;

        // bounding box untuk pre-filter
        $km_scale = 0.008983112;
        $lng_ratio = 1 / cos(deg2rad($lat));

        $north = $lat + ($radiusKm * $km_scale);
        $south = $lat - ($radiusKm * $km_scale);
        $east  = $lon + ($radiusKm * $km_scale * $lng_ratio);
        $west  = $lon - ($radiusKm * $km_scale * $lng_ratio);

        $results = DB::connection('tis_master')->select("
        SELECT
            (6371 * ACOS(
                COS(RADIANS(?)) *
                COS(RADIANS(ODP_Latitude)) *
                COS(RADIANS(ODP_Longitude) - RADIANS(?)) +
                SIN(RADIANS(?)) *
                SIN(RADIANS(ODP_Latitude))
            )) * 1000 AS distance_m
        FROM tis_master.alpro
        WHERE
            ODP_Latitude BETWEEN ? AND ?
            AND ODP_Longitude BETWEEN ? AND ?
        HAVING distance_m <= ?
        ORDER BY distance_m
        LIMIT 1
    ", [
            $lat,
            $lon,
            $lat,
            $south,
            $north,
            $west,
            $east,
            $radiusMeters
        ]);

        return count($results) > 0;
    }

    public static function trimObject($data)
    {
        if (!is_array($data) && !is_object($data)) {
            return $data;
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        // remove spasi di antara quotes
        $cleaned = preg_replace(['/\"\s+/', '/\s+\"/'], '"', $json);
        $cleaned = str_replace("'", "`", $cleaned);

        return json_decode($cleaned, true);
    }

    public static function getBandwidthFromProductName($payload)
    {
        $product_name = $payload['product_name'] ?? '';

        // regex mirip dengan TypeScript: ambil 6 karakter sebelum 'Mbps'
        $pattern = '/.{6}\b(Mbps)\b/';

        if (!preg_match($pattern, $product_name, $matches)) {
            return null;
        }

        // hapus semua non-digit, ambil angka saja
        $bandwidth = preg_replace('/\D/', '', $matches[0]);

        return $bandwidth ?: null;
    }

    public static function emptyOrRows($rows)
    {
        return $rows ?? [];
    }
}
