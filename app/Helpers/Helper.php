<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use phpseclib3\Net\SFTP;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;

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

        if (is_object($payload)) {
            $payload = [
                'user_id' => $payload->user_id ?? $payload->sub ?? null, // 🔥 pakai sub jika user_id tidak ada
                'email'   => $payload->email ?? null,
                'role'    => $payload->role ?? null,
                'area'    => $payload->area ?? null,
                'position_code' => $payload->position_code ?? null,
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

    public static function getAuthorizationTokenData(Request $request): ?array
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            return null;
        }

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];

        if ($token === '{{jwt_token}}') {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $decodedArr = json_decode(json_encode($decoded), true);

            // 🔥 fallback ke sub jika user_id tidak ada
            if (!isset($decodedArr['user_id']) && isset($decodedArr['sub'])) {
                $decodedArr['user_id'] = $decodedArr['sub'];
            }

            return $decodedArr;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Ambil token dari header Authorization
     * Format: Bearer xxxxx
     */
    private static function getTokenFromHeaders(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    //  public static function getOSSAssetURL()
    // {
    //     return env('OSS_ASSET_URL', 'https://example.com/oss');
    // }

    /**
     * Fetch a file from OSS Storage
     */
    public static function fetchFileFromOSS($filePath)
    {
        $ossUrl = self::getOSSAssetURL();
        $fileUrl = "{$ossUrl}/{$filePath}";

        // You can use file_get_contents or any library like Guzzle to fetch the file
        return file_get_contents($fileUrl);
    }

    /**
     * Utility method to download a file from OSS Storage
     * (Assuming OSS files are public)
     */
    public static function downloadFileFromOSS($filePath, $destination)
    {
        $content = self::fetchFileFromOSS($filePath);

        file_put_contents($destination, $content);
    }

    public static function getOSSAssetURL()
    {
        // Menentukan base URL berdasarkan environment
        if (env('APP_ENV') == 'local') {
            // Development environment
            $base_url = 'http://' . env('DEV_OSS_HOST');
        } elseif (env('APP_ENV') == 'production') {
            // Production environment
            $base_url = 'https://' . env('PRODUCTION_OSS_DOMAIN');
        } else {
            // Default jika environment tidak ditemukan
            $base_url = 'http://localhost';
        }

        return $base_url;
    }

    public static function getProfileSales($user_id)
    {
        $result = DB::select(
            'SELECT * FROM tis_main.user_l WHERE UserID = ?',
            [$user_id]
        );

        return [
            'status' => 'SUCCESS',
            'data'   => $result,
        ];
    }

    public static function rupiahFormatter(array $payload)
    {
        $amount = $payload['amount'] ?? 0;

        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public static function utcNow()
    {
        return Carbon::now('UTC')->format('Y-m-d H:i:s');
    }

    public static function generateFABV2(array $payload)
    {

        $pdf = Pdf::loadView('pdf.fab', $payload)
            ->setPaper('legal', 'portrait');

        return $pdf->output();
    }

    public function uploadMultipleBuffer(array $files)
    {
        foreach ($files as $file) {
            $dir = dirname($file['target_dir']);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($file['target_dir'], $file['buffer']);
        }
    }

    public static function uploadBufferToLocal(array $payload)
    {
        foreach ($payload as $file) {
            if (!isset($file['buffer'], $file['target_dir'])) {
                throw new \Exception('Invalid upload payload');
            }

            // pastikan folder ada
            $dir = dirname($file['target_dir']);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($file['target_dir'], $file['buffer']);
        }

        return true;
    }

    // public static function deleteFileFromServer(string $filename): array
    // {
    // try {
    //     // ❌ jangan pakai exists()
    //     Storage::disk('sftp_oss')->delete($filename);

    //     return [
    //         'status' => 'success',
    //         'code'   => 200,
    //     ];
    // } catch (\Throwable $e) {

    //     // kalau file memang tidak ada → anggap aman
    //     if (
    //         str_contains($e->getMessage(), 'Unable to check existence') ||
    //         str_contains($e->getMessage(), 'No such file')
    //     ) {
    //         return [
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => 'File not found, skipped delete',
    //         ];
    //     }

    //     throw $e;
    //     }
    // }

}
