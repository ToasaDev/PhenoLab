<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelaObservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TelaObservationController extends Controller
{
    /**
     * Paginated list of Tela Botanica observations with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TelaObservation::query();

        if ($year = $request->query('year')) {
            $query->where('year', $year);
        }

        if ($genus = $request->query('genus')) {
            $query->where('genus', $genus);
        }

        if ($stageCode = $request->query('stage_code')) {
            $query->where('stage_code', $stageCode);
        }

        if ($eventCode = $request->query('phenological_main_event_code')) {
            $query->where('phenological_main_event_code', $eventCode);
        }

        if ($env = $request->query('environment')) {
            $query->where('environment', $env);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('binomial_name', 'like', "%{$search}%")
                  ->orWhere('genus', 'like', "%{$search}%")
                  ->orWhere('species', 'like', "%{$search}%")
                  ->orWhere('site_name', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('date');

        $perPage = min((int) $request->query('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Show a single Tela observation.
     */
    public function show(int $id): JsonResponse
    {
        $observation = TelaObservation::findOrFail($id);

        return response()->json($observation);
    }

    /**
     * Filter Tela observations by taxon ID.
     */
    public function byTaxon(Request $request): JsonResponse
    {
        $request->validate([
            'taxon_id' => ['required', 'string'],
        ]);

        $observations = TelaObservation::where('taxon_id', $request->query('taxon_id'))
            ->orderByDesc('date')
            ->get();

        return response()->json($observations);
    }

    /**
     * Statistics across Tela observations.
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = TelaObservation::query();

        // Apply same filters as index
        if ($year = $request->query('year'))              $query->where('year', $year);
        if ($genus = $request->query('genus'))             $query->where('genus', $genus);
        if ($stageCode = $request->query('stage_code'))    $query->where('stage_code', $stageCode);

        $total = $query->count();

        $byStage = (clone $query)
            ->selectRaw('stage_code, stage_description, count(*) as count')
            ->groupBy('stage_code', 'stage_description')
            ->orderBy('stage_code')
            ->get();

        $byYear = (clone $query)
            ->selectRaw('year, count(*) as count')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        $byGenus = (clone $query)
            ->selectRaw('genus, count(*) as count')
            ->groupBy('genus')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        return response()->json([
            'total'    => $total,
            'by_stage' => $byStage,
            'by_year'  => $byYear,
            'by_genus' => $byGenus,
        ]);
    }
}
