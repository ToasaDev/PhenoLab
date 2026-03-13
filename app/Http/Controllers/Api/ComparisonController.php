<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Models\ODSObservation;
use App\Models\Plant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComparisonController extends Controller
{
    /**
     * Compare user observations with ODS national data.
     *
     * Returns the user's observations for the given plant + stage alongside
     * aggregate ODS day_of_year statistics for the same species and stage.
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'plant_id'   => ['required', 'exists:plants,id'],
            'stage_code' => ['required', 'string'],
        ]);

        $plantId = $request->query('plant_id');
        $stageCode = $request->query('stage_code');

        $plant = Plant::with('taxon:id,binomial_name,common_name_fr')->findOrFail($plantId);

        // User observations for this plant and stage
        $userObservations = Observation::where('plant_id', $plantId)
            ->whereHas('phenologicalStage', fn ($q) => $q->where('stage_code', $stageCode))
            ->with('phenologicalStage:id,stage_code,stage_description')
            ->orderBy('observation_date')
            ->get()
            ->map(function ($obs) {
                return [
                    'id'               => $obs->id,
                    'observation_date' => $obs->observation_date,
                    'day_of_year'      => $obs->day_of_year,
                    'year'             => $obs->observation_date?->format('Y'),
                    'stage_code'       => $obs->phenologicalStage->stage_code ?? null,
                    'stage_description'=> $obs->phenologicalStage->stage_description ?? null,
                ];
            });

        // SQLite-compatible day-of-year and year expressions
        $driver = DB::connection()->getDriverName();
        $doyExpr = match ($driver) {
            'sqlite' => "CAST(strftime('%j', date) AS INTEGER)",
            'pgsql'  => 'EXTRACT(DOY FROM date)::integer',
            default  => 'DAYOFYEAR(date)',
        };
        $yearExpr = match ($driver) {
            'sqlite' => "CAST(strftime('%Y', date) AS INTEGER)",
            'pgsql'  => 'EXTRACT(YEAR FROM date)::integer',
            default  => 'YEAR(date)',
        };

        // ODS comparison data (national statistics)
        $odsStats = ODSObservation::where('scientific_name', $plant->taxon->binomial_name)
            ->where('bbch_code', $stageCode)
            ->whereNotNull('date')
            ->selectRaw("
                count(*) as total_observations,
                avg({$doyExpr}) as avg_day_of_year,
                min({$doyExpr}) as min_day_of_year,
                max({$doyExpr}) as max_day_of_year
            ")
            ->first();

        // ODS observations by year
        $odsByYear = ODSObservation::where('scientific_name', $plant->taxon->binomial_name)
            ->where('bbch_code', $stageCode)
            ->whereNotNull('date')
            ->selectRaw("{$yearExpr} as obs_year, avg({$doyExpr}) as avg_day_of_year, count(*) as obs_count")
            ->groupByRaw("{$yearExpr}")
            ->orderByRaw('obs_year')
            ->get()
            ->map(fn ($row) => [
                'year' => (int) $row->obs_year,
                'avg_day_of_year' => round((float) $row->avg_day_of_year, 1),
                'count' => (int) $row->obs_count,
            ]);

        $totalOds = (int) ($odsStats->total_observations ?? 0);
        $avgDoy = $odsStats->avg_day_of_year ? round((float) $odsStats->avg_day_of_year, 1) : null;

        // Build comparison with user's latest observation
        $latestUser = $userObservations->last();
        $comparisonPossible = $totalOds > 0 && $latestUser && $avgDoy !== null;
        $diffDays = null;
        $status = 'no_data';
        $statusLabel = 'Données insuffisantes';

        if ($comparisonPossible && $latestUser['day_of_year']) {
            $diffDays = round($latestUser['day_of_year'] - $avgDoy, 1);
            if (abs($diffDays) <= 7) {
                $status = 'normal';
                $statusLabel = 'Dans la moyenne nationale';
            } elseif ($diffDays > 0) {
                $status = 'late';
                $statusLabel = 'En retard par rapport à la moyenne nationale';
            } else {
                $status = 'early';
                $statusLabel = 'En avance par rapport à la moyenne nationale';
            }
        }

        return response()->json([
            'plant' => [
                'id'            => $plant->id,
                'name'          => $plant->name,
                'binomial_name' => $plant->taxon->binomial_name,
                'common_name'   => $plant->taxon->common_name_fr,
            ],
            'stage_code'        => $stageCode,
            'user_observations' => $userObservations,
            'national_comparison' => [
                'comparison_possible' => $comparisonPossible,
                'message' => $comparisonPossible ? null : 'Aucune donnée ODS disponible pour cette espèce et ce stade',
                'user_observation' => $latestUser ? [
                    'day_of_year' => $latestUser['day_of_year'],
                    'date' => $latestUser['observation_date'],
                ] : null,
                'national_statistics' => [
                    'total_observations' => $totalOds,
                    'avg_day_of_year'    => $avgDoy,
                    'min_day_of_year'    => $odsStats->min_day_of_year ? (int) $odsStats->min_day_of_year : null,
                    'max_day_of_year'    => $odsStats->max_day_of_year ? (int) $odsStats->max_day_of_year : null,
                ],
                'comparison' => [
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'diff_from_mean_days' => $diffDays,
                ],
            ],
            'ods_by_year' => $odsByYear,
        ]);
    }
}
