<?php

namespace App\Services\Customer;

use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RegionService
{
    public static function getZipCode(array $qs)
    {
        $listPerPage = $qs['list_per_page'] ?? 50;
        $page = $qs['page'] ?? 1;
        $offset = Helper::getOffset($page, $listPerPage);
        $queryString = $qs['q_search'] ?? '%';

        $rows = DB::connection('tis_master')->select(
            "SELECT * FROM master_kodepos
             WHERE City LIKE ? OR District LIKE ? OR ZipCode LIKE ? OR Regional LIKE ?
             ORDER BY ZipCode, District, City, Province
             LIMIT ?, ?",
            ["%{$queryString}%", "%{$queryString}%", "%{$queryString}%", "%{$queryString}%", $offset, $listPerPage]
        );

        foreach ($rows as &$item) {
            $item->Display_Name = "{$item->ZipCode} => {$item->District}, {$item->City}, {$item->Province}";
        }

        return [
            'status' => 'success',
            'meta' => ['page' => $page, 'list_per_page' => $listPerPage],
            'data' => Helper::emptyOrRows($rows)
        ];
    }

    public static function getRegion(array $qs, ?array $session)
    {
        $listPerPage = $qs['list_per_page'] ?? 50;
        $page = $qs['page'] ?? 1;
        $offset = Helper::getOffset($page, $listPerPage);
        $regionType = $qs['region_type'] ?? 'master_kodepos';

        $rows = [];

        if ($regionType === 'COMMON') {
            $rows = DB::connection('tis_master')->select("SELECT * FROM master_kodepos LIMIT ?, ?", [$offset, $listPerPage]);
        } elseif ($regionType === 'OSS_ALL') {
            $rows = DB::connection('tis_master')->select(
                "SELECT ID, Company_Name, Company_Alias, Region, Region_Alias FROM region LIMIT ?, ?",
                [$offset, $listPerPage]
            );
        } elseif ($regionType === 'OSS_SALES' && $session && ($session['role'] ?? '') === 'SALES') {
            $rows = DB::connection('tis_master')->select(
                "SELECT ID, Company_Name, Company_Alias, Region, Region_Alias FROM region WHERE Is_Active = 1 LIMIT ?, ?",
                [$offset, $listPerPage]
            );
        }

        return [
            'status' => 'success',
            'meta' => ['page' => $page, 'list_per_page' => $listPerPage],
            'data' => Helper::emptyOrRows($rows)
        ];
    }

    public static function confirmIfAreaIsCovered(array $qs)
    {
        $lat = $qs['latitude'] ?? null;
        $lon = $qs['longitude'] ?? null;

        if (!$lat || !$lon) {
            throw new HttpException(422, 'latitude dan longitude tidak boleh kosong');
        }

        // logika coverage sementara
        $isCovered = true;

        if (!$isCovered) {
            throw new HttpException(404, 'Mohon maaf, area anda tidak dalam coverage kami');
        }

        return [
            'status' => 'success',
            'message' => 'Area anda dalam coverage',
            'data' => ['is_covered' => $isCovered]
        ];
    }

    public static function getODPList(array $qs)
    {
        $listPerPage = $qs['list_per_page'] ?? 50;
        $page = $qs['page'] ?? 1;
        $offset = Helper::getOffset($page, $listPerPage);

        $rows = DB::connection('tis_master')->select(
            "SELECT ID, POP_Name, Region, Province, City, POP_Address, Latitude, Longitude, Status
             FROM a_pop_data LIMIT ?, ?",
            [$offset, $listPerPage]
        );

        return [
            'status' => 'success',
            'meta' => ['page' => $page, 'list_per_page' => $listPerPage],
            'data' => Helper::emptyOrRows($rows)
        ];
    }

    public static function getNearestODP(array $qs, array $session)
    {
        $listPerPage = $qs['list_per_page'] ?? 50;
        $page = $qs['page'] ?? 1;
        $offset = Helper::getOffset($page, $listPerPage);

        $lat = $qs['latitude'] ?? null;
        $lon = $qs['longitude'] ?? null;

        if (!$lat || !$lon) {
            throw new HttpException(422, 'latitude dan longitude tidak boleh kosong');
        }

        // Query Haversine untuk ODP terdekat
        $rows = DB::connection('tis_master')->select(
            "SELECT ODP_ID, POP_ID, Latitude, Longitude, ODP_Latitude, ODP_Longitude,
                ( 6371 * acos( cos( radians(?) ) * cos( radians(ODP_Latitude) )
                * cos( radians(ODP_Longitude) - radians(?) ) + sin( radians(?) )
                * sin( radians(ODP_Latitude) ) ) ) AS distance
         FROM alpro
         ORDER BY distance
         LIMIT ?, ?",
            [$lat, $lon, $lat, $offset, $listPerPage]
        );

        // Ubah string latitude/longitude jadi float
        foreach ($rows as &$item) {
            $item->Latitude = (float)$item->Latitude;
            $item->Longitude = (float)$item->Longitude;
            $item->ODP_Latitude = (float)$item->ODP_Latitude;
            $item->ODP_Longitude = (float)$item->ODP_Longitude;
            $item->distance = (float)$item->distance; // jarak dalam km
        }

        return [
            'status' => 'success',
            'meta' => ['page' => $page, 'list_per_page' => $listPerPage],
            'data' => Helper::emptyOrRows($rows)
        ];
    }
}
