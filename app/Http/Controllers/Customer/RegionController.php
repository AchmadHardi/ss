<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Customer\RegionService;
use App\Helpers\Helper;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RegionController extends Controller
{
    public function zipCode(Request $request)
    {
        $data = RegionService::getZipCode($request->query());
        return response()->json($data);
    }

    public function region(Request $request)
    {
        $session = Helper::getAuthorizationTokenData($request);
        $data = RegionService::getRegion($request->query(), $session);
        return response()->json($data);
    }

    public function checkCoverage(Request $request)
    {
        $data = RegionService::confirmIfAreaIsCovered($request->query());
        return response()->json($data);
    }

    public function odpList(Request $request)
    {
        $data = RegionService::getODPList($request->query());
        return response()->json($data);
    }

    public function nearestODP(Request $request)
    {
        $session = Helper::getAuthorizationTokenData($request);
        if (!$session) {
            throw new HttpException(403, "Silahkan login terlebih dahulu");
        }

        $data = RegionService::getNearestODP($request->query(), $session);
        return response()->json($data);
    }
}
