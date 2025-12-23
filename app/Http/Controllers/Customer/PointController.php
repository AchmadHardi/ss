<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\PointService;
use Illuminate\Http\Request;

class PointController extends Controller
{
    protected PointService $service;

    public function __construct(PointService $service)
    {
        $this->service = $service;
    }

    public function getTotal(Request $request)
    {
        return response()->json(
            $this->service->getTotalPoints($request->user())
        );
    }

    public function addPoint(Request $request)
    {
        // Ambil user dari request (auth)
        $user = $request->user();

        // Panggil service dengan body + user
        $result = $this->service->rewardPoints(
            $request->all(),
            $user
        );

        return response()->json($result);
    }

    public function redeem(Request $request)
    {
        $user = $request->user();

        return response()->json(
            $this->service->redeemPoints($request->all(), $user)
        );
    }

    public function getRewards(Request $request)
    {
        $result = app(PointService::class)
            ->getAvailableRewardsForPromo($request->query());

        return response()->json($result, 200);
    }

    public function adsWatch(Request $request)
    {
        return response()->json(
            $this->service->rewardPointPerAdsWatched(
                $request->all(),
                $request->user()
            )
        );
    }

    public function presence(Request $request)
    {
        return response()->json(
            $this->service->rewardPointPerPresence(
                $request->all(),
                $request->user()
            )
        );
    }
    
    public function transactions(Request $request)
    {
        // Ambil user dari request (auth)
        $user = $request->user();

        // Panggil service
        $result = $this->service->getHistoryPoints($user);

        return response()->json($result);
    }
}
