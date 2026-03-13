<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Models\Plant;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends Controller
{
    /**
     * Global and per-user statistics.
     */
    public function index(): JsonResponse
    {
        $global = [
            'total_sites'        => Site::count(),
            'total_plants'       => Plant::count(),
            'total_observations' => Observation::count(),
        ];

        $user = null;

        if (Auth::check()) {
            $userId = Auth::id();

            $user = [
                'sites_count'        => Site::where('owner_id', $userId)->count(),
                'plants_count'       => Plant::where('owner_id', $userId)->count(),
                'observations_count' => Observation::where('observer_id', $userId)->count(),
            ];
        }

        return response()->json([
            'global' => $global,
            'user'   => $user,
        ]);
    }
}
