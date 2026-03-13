<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ODSObservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ODSController extends Controller
{
    /**
     * Search ODS observations with various filters.
     */
    public function search(Request $request): JsonResponse
    {
        $query = ODSObservation::query();

        if ($v = $request->query('scientific_name')) {
            $query->where('scientific_name', 'like', '%' . $this->escapeLike($v) . '%');
        }

        if ($v = $request->query('vernacular_name')) {
            $query->where('vernacular_name', 'like', '%' . $this->escapeLike($v) . '%');
        }

        if ($v = $request->query('phenological_stage')) {
            $query->where('phenological_stage', 'like', '%' . $this->escapeLike($v) . '%');
        }

        if ($v = $request->query('bbch_code')) {
            $query->where('bbch_code', $v);
        }

        if ($v = $request->query('department')) {
            $query->where('department', 'like', '%' . $this->escapeLike($v) . '%');
        }

        if ($v = $request->query('habitat')) {
            $query->where('habitat', 'like', '%' . $this->escapeLike($v) . '%');
        }

        if ($v = $request->query('year')) {
            $query->whereYear('date', $v);
        }

        if ($v = $request->query('station_name')) {
            $query->where('station_name', 'like', '%' . $this->escapeLike($v) . '%');
        }

        $query->orderByDesc('date');

        $limit = min((int) ($request->query('limit', 100)), 1000);
        $offset = (int) $request->query('offset', 0);

        $total = $query->count();
        $results = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total'   => $total,
            'limit'   => $limit,
            'offset'  => $offset,
            'results' => $results,
        ]);
    }

    /**
     * ODS statistics: totals, unique counts, date ranges, top species/departments.
     */
    public function stats(): JsonResponse
    {
        $total = ODSObservation::count();

        $uniqueSpecies = ODSObservation::distinct('scientific_name')->count('scientific_name');
        $uniqueStations = ODSObservation::distinct('station_name')->count('station_name');
        $uniqueDepartments = ODSObservation::distinct('department')->count('department');

        $dateRange = ODSObservation::selectRaw('min(date) as min_date, max(date) as max_date')->first();

        $topSpecies = ODSObservation::selectRaw('scientific_name, count(*) as count')
            ->groupBy('scientific_name')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        $topDepartments = ODSObservation::selectRaw('department, count(*) as count')
            ->where('department', '!=', '')
            ->groupBy('department')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        return response()->json([
            'total'              => $total,
            'unique_species'     => $uniqueSpecies,
            'unique_stations'    => $uniqueStations,
            'unique_departments' => $uniqueDepartments,
            'date_range'         => [
                'min' => $dateRange->min_date,
                'max' => $dateRange->max_date,
            ],
            'top_species'     => $topSpecies,
            'top_departments' => $topDepartments,
        ]);
    }

    /**
     * ODS observation counts by year for chart display.
     */
    public function evolution(): JsonResponse
    {
        $driver = DB::connection()->getDriverName();

        $yearExpression = match ($driver) {
            'sqlite' => "CAST(strftime('%Y', date) AS INTEGER)",
            'mysql', 'mariadb' => 'YEAR(date)',
            'pgsql' => 'EXTRACT(YEAR FROM date)',
            default => 'YEAR(date)',
        };

        $data = ODSObservation::query()
            ->whereNotNull('date')
            ->selectRaw("{$yearExpression} as obs_year, count(*) as obs_count")
            ->groupByRaw($yearExpression)
            ->orderByRaw('obs_year')
            ->get()
            ->map(fn ($row) => [
                'year'  => (int) $row->obs_year,
                'count' => (int) $row->obs_count,
            ]);

        $years = $data->pluck('year')->values();
        $counts = $data->pluck('count')->values();

        return response()->json([
            'chart_data' => [
                'years' => $years,
                'counts' => $counts,
            ],
            'summary' => [
                'total_observations' => $counts->sum(),
                'first_year' => $years->first(),
                'last_year' => $years->last(),
            ],
        ]);
    }
}
