<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ObservationController extends Controller
{
    use Concerns\SanitizesOrdering;
    private function visibleObservationsQuery(): Builder
    {
        $query = Observation::query();
        $user = Auth::user();

        if ($user?->is_staff) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($user) {
            $visible->where(function (Builder $public) {
                $public->where('is_public', true)
                    ->whereHas('plant', function (Builder $plant) {
                        $plant->where('is_private', false)
                            ->whereHas('site', fn (Builder $site) => $site->where('is_private', false));
                    });
            });

            if ($user !== null) {
                $visible->orWhere('observer_id', $user->id)
                    ->orWhereHas('plant', fn (Builder $plant) => $plant->where('owner_id', $user->id));
            }
        });
    }

    /**
     * Paginated list of observations with extensive filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->visibleObservationsQuery()->with(
            'plant:id,name,taxon_id,category_id,site_id',
            'plant.taxon:id,binomial_name,common_name_fr',
            'plant.category:id,name',
            'plant.site:id,name',
            'phenologicalStage:id,stage_code,stage_description,main_event_code',
            'observer:id,name'
        )->withCount('photos');

        if ($search = $request->query('search')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('plant', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($v = $request->query('plant'))              $query->where('plant_id', $v);
        if ($v = $request->query('phenological_stage'))  $query->where('phenological_stage_id', $v);
        if ($v = $request->query('observer'))            $query->where('observer_id', $v);

        if ($request->has('is_validated')) {
            $query->where('is_validated', $request->boolean('is_validated'));
        }

        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        if ($v = $request->query('date_from')) $query->where('observation_date', '>=', $v);
        if ($v = $request->query('date_to'))   $query->where('observation_date', '<=', $v);

        if ($year = $request->query('year')) {
            $query->whereYear('observation_date', $year);
        }

        if ($site = $request->query('site')) {
            $query->whereHas('plant', fn ($q) => $q->where('site_id', $site));
        }

        if ($taxon = $request->query('taxon')) {
            $query->whereHas('plant', fn ($q) => $q->where('taxon_id', $taxon));
        }

        if ($category = $request->query('category')) {
            $query->whereHas('plant', fn ($q) => $q->where('category_id', $category));
        }

        if ($stage = $request->query('stage')) {
            $query->whereHas('phenologicalStage', fn ($q) => $q->where('stage_code', $stage));
        }

        if ($request->has('has_photos')) {
            $request->boolean('has_photos')
                ? $query->has('photos')
                : $query->doesntHave('photos');
        }

        [$column, $direction] = $this->parseOrdering(
            $request->query('ordering', '-observation_date'),
            ['observation_date', 'created_at', 'plant_id', 'intensity', 'confidence_level', 'id'],
            'observation_date'
        );
        $query->orderBy($column, $direction);

        $perPage = min((int) $request->query('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Show a single observation with nested relations.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $observation = $this->visibleObservationsQuery()->with([
            'plant.taxon',
            'plant.category:id,name',
            'plant.site:id,name',
            'phenologicalStage',
            'observer:id,name',
            'validatedBy:id,name',
            'photos' => function ($query) use ($user) {
                $query->when(! $user?->is_staff, function ($visible) use ($user) {
                    $visible->where(function ($scope) use ($user) {
                        $scope->where('is_public', true);

                        if ($user !== null) {
                            $scope->orWhere('photographer_id', $user->id);
                        }
                    });
                });
            },
        ])->findOrFail($id);

        return response()->json($observation);
    }

    /**
     * Create a new observation.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'observation_date'      => ['required', 'date'],
            'plant_id'              => ['required', 'exists:plants,id'],
            'phenological_stage_id' => ['required', 'exists:phenological_stages,id'],
            'intensity'             => ['nullable', 'integer', 'between:1,5'],
            'temperature'           => ['nullable', 'numeric', 'between:-50,60'],
            'weather_condition'     => ['nullable', 'string', 'in:ensoleillé,nuageux,pluvieux,venteux,orageux'],
            'humidity'              => ['nullable', 'integer', 'between:0,100'],
            'wind_speed'            => ['nullable', 'numeric', 'min:0'],
            'notes'                 => ['nullable', 'string'],
            'confidence_level'      => ['nullable', 'integer', 'between:1,5'],
            'is_public'             => ['nullable', 'boolean'],
            'time_of_day'           => ['nullable', 'date_format:H:i'],
        ]);

        $plant = Plant::with('site:id,owner_id')->findOrFail($data['plant_id']);

        if (! Auth::user()?->is_staff && (int) $plant->owner_id !== (int) Auth::id()) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $data['observer_id'] = Auth::id();
        $data['confidence_level'] = $data['confidence_level'] ?? 3;
        $data['is_public'] = $data['is_public'] ?? true;

        // Auto-calculate day_of_year
        $date = \Carbon\Carbon::parse($data['observation_date']);
        $data['day_of_year'] = $date->dayOfYear;

        $observation = Observation::create($data);

        return response()->json(
            $observation->load(
                'plant:id,name',
                'phenologicalStage:id,stage_code,stage_description',
                'observer:id,name'
            ),
            201
        );
    }

    /**
     * Update an observation (observer or staff only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $observation = Observation::findOrFail($id);

        if (Auth::id() !== $observation->observer_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        // Convert empty strings to null for nullable fields
        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->only([
            'weather_condition', 'notes', 'time_of_day',
        ])));

        $data = $request->validate([
            'observation_date'      => ['sometimes', 'required', 'date'],
            'plant_id'              => ['sometimes', 'required', 'exists:plants,id'],
            'phenological_stage_id' => ['sometimes', 'required', 'exists:phenological_stages,id'],
            'intensity'             => ['nullable', 'integer', 'between:1,5'],
            'temperature'           => ['nullable', 'numeric', 'between:-50,60'],
            'weather_condition'     => ['nullable', 'string', 'in:ensoleillé,nuageux,pluvieux,venteux,orageux'],
            'humidity'              => ['nullable', 'integer', 'between:0,100'],
            'wind_speed'            => ['nullable', 'numeric', 'min:0'],
            'notes'                 => ['nullable', 'string'],
            'confidence_level'      => ['nullable', 'integer', 'between:1,5'],
            'is_public'             => ['nullable', 'boolean'],
            'time_of_day'           => ['nullable', 'date_format:H:i'],
        ]);

        // Recalculate day_of_year if date changed
        if (isset($data['observation_date'])) {
            $date = \Carbon\Carbon::parse($data['observation_date']);
            $data['day_of_year'] = $date->dayOfYear;
        }

        $observation->update($data);

        return response()->json($observation->load(
            'plant:id,name',
            'phenologicalStage:id,stage_code,stage_description',
            'observer:id,name'
        ));
    }

    /**
     * Delete an observation (observer or staff only).
     */
    public function destroy(int $id): JsonResponse
    {
        $observation = Observation::findOrFail($id);

        if (Auth::id() !== $observation->observer_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $observation->delete();

        return response()->json(null, 204);
    }

    /**
     * Return observations by the current user.
     */
    public function myObservations(Request $request): JsonResponse
    {
        $observations = Observation::where('observer_id', Auth::id())
            ->with(
                'plant:id,name,taxon_id',
                'plant.taxon:id,binomial_name,common_name_fr',
                'phenologicalStage:id,stage_code,stage_description'
            )
            ->withCount('photos')
            ->orderByDesc('observation_date')
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json($observations);
    }

    /**
     * Observations for a specific plant.
     */
    public function byPlant(Request $request): JsonResponse
    {
        $request->validate([
            'plant_id' => ['required', 'exists:plants,id'],
        ]);

        $observations = $this->visibleObservationsQuery()
            ->where('plant_id', $request->query('plant_id'))
            ->with('phenologicalStage:id,stage_code,stage_description', 'observer:id,name')
            ->withCount('photos')
            ->orderByDesc('observation_date')
            ->get();

        return response()->json($observations);
    }

    /**
     * Group observations by phenological stage.
     */
    public function byStage(): JsonResponse
    {
        $observations = $this->visibleObservationsQuery()->with(
            'plant:id,name',
            'phenologicalStage:id,stage_code,stage_description'
        )
        ->orderByDesc('observation_date')
        ->get()
        ->groupBy('phenological_stage_id');

        return response()->json($observations);
    }

    /**
     * Return distinct years from observations.
     */
    public function yearsAvailable(): JsonResponse
    {
        $yearExpression = $this->yearExpression('observation_date');

        $years = $this->visibleObservationsQuery()
            ->selectRaw("distinct {$yearExpression} as year")
            ->whereNotNull('observation_date')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->values();

        return response()->json([
            'years' => $years,
        ]);
    }

    /**
     * Return monthly observation counts, optionally filtered by year.
     */
    public function monthlyCounts(Request $request): JsonResponse
    {
        $monthExpression = $this->monthExpression('observation_date');
        $baseQuery = $this->visibleObservationsQuery();

        if ($year = $request->query('year')) {
            $baseQuery->whereYear('observation_date', $year);
        }

        $monthlyRows = (clone $baseQuery)
            ->selectRaw("{$monthExpression} as month, count(*) as count")
            ->groupByRaw($monthExpression)
            ->orderBy('month')
            ->get();

        $monthCounts = collect(range(1, 12))->mapWithKeys(fn (int $month) => [$month => 0]);
        foreach ($monthlyRows as $row) {
            $monthCounts[(int) $row->month] = (int) $row->count;
        }

        $monthly = [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            'data' => $monthCounts->values()->all(),
        ];

        $stageRows = (clone $baseQuery)
            ->join('phenological_stages', 'observations.phenological_stage_id', '=', 'phenological_stages.id')
            ->selectRaw('phenological_stages.stage_description as phenological_stage__stage_description, count(*) as count')
            ->groupBy('phenological_stages.id', 'phenological_stages.stage_description')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'phenological_stage__stage_description' => $row->phenological_stage__stage_description,
                'count' => (int) $row->count,
            ])
            ->values();

        $summary = [
            'total_observations' => (clone $baseQuery)->count(),
            'unique_plants' => (clone $baseQuery)->distinct('plant_id')->count('plant_id'),
            'unique_sites' => (clone $baseQuery)
                ->join('plants', 'observations.plant_id', '=', 'plants.id')
                ->distinct('plants.site_id')
                ->count('plants.site_id'),
            'validated_count' => (clone $baseQuery)->where('is_validated', true)->count(),
            'with_photos_count' => (clone $baseQuery)->has('photos')->count(),
        ];

        // Top observed plants (top 10)
        $topPlants = (clone $baseQuery)
            ->join('plants', 'observations.plant_id', '=', 'plants.id')
            ->leftJoin('taxons', 'plants.taxon_id', '=', 'taxons.id')
            ->selectRaw('plants.id, plants.name, taxons.binomial_name, taxons.common_name_fr, count(*) as count')
            ->groupBy('plants.id', 'plants.name', 'taxons.binomial_name', 'taxons.common_name_fr')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Observations by site
        $bySite = (clone $baseQuery)
            ->join('plants', 'observations.plant_id', '=', 'plants.id')
            ->join('sites', 'plants.site_id', '=', 'sites.id')
            ->selectRaw('sites.id, sites.name, count(*) as count')
            ->groupBy('sites.id', 'sites.name')
            ->orderByDesc('count')
            ->get();

        // Observations by category
        $byCategory = (clone $baseQuery)
            ->join('plants', 'observations.plant_id', '=', 'plants.id')
            ->join('categories', 'plants.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, categories.category_type, count(*) as count')
            ->groupBy('categories.name', 'categories.category_type')
            ->orderByDesc('count')
            ->get();

        // Intensity distribution
        $byIntensity = (clone $baseQuery)
            ->whereNotNull('intensity')
            ->selectRaw('intensity, count(*) as count')
            ->groupBy('intensity')
            ->orderBy('intensity')
            ->get();

        // Weather distribution
        $byWeather = (clone $baseQuery)
            ->whereNotNull('weather_condition')
            ->where('weather_condition', '!=', '')
            ->selectRaw('weather_condition, count(*) as count')
            ->groupBy('weather_condition')
            ->orderByDesc('count')
            ->get();

        // Main phenological events (grouped by main_event_code)
        $byMainEvent = (clone $baseQuery)
            ->join('phenological_stages', 'observations.phenological_stage_id', '=', 'phenological_stages.id')
            ->whereNotNull('phenological_stages.main_event_code')
            ->selectRaw('phenological_stages.main_event_code, phenological_stages.main_event_description, count(*) as count')
            ->groupBy('phenological_stages.main_event_code', 'phenological_stages.main_event_description')
            ->orderBy('phenological_stages.main_event_code')
            ->get();

        // Recent observations (last 5)
        $recent = (clone $baseQuery)
            ->with('plant:id,name', 'phenologicalStage:id,stage_code,stage_description', 'observer:id,name')
            ->orderByDesc('observation_date')
            ->limit(5)
            ->get()
            ->map(fn ($obs) => [
                'id' => $obs->id,
                'date' => $obs->observation_date?->format('Y-m-d'),
                'plant_name' => $obs->plant?->name,
                'stage' => $obs->phenologicalStage?->stage_description,
                'stage_code' => $obs->phenologicalStage?->stage_code,
                'observer' => $obs->observer?->name,
            ]);

        return response()->json([
            'monthly' => $monthly,
            'by_stage' => $stageRows,
            'summary' => $summary,
            'top_plants' => $topPlants,
            'by_site' => $bySite,
            'by_category' => $byCategory,
            'by_intensity' => $byIntensity,
            'by_weather' => $byWeather,
            'by_main_event' => $byMainEvent,
            'recent' => $recent,
        ]);
    }

    private function yearExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%Y', {$column}) AS INTEGER)",
            'mysql', 'mariadb' => "YEAR({$column})",
            'pgsql' => "EXTRACT(YEAR FROM {$column})",
            default => "YEAR({$column})",
        };
    }

    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', {$column}) AS INTEGER)",
            'mysql', 'mariadb' => "MONTH({$column})",
            'pgsql' => "EXTRACT(MONTH FROM {$column})",
            default => "MONTH({$column})",
        };
    }

    /**
     * Validate an observation (staff only).
     */
    public function validateObservation(Request $request, int $id): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        $observation = Observation::findOrFail($id);

        $observation->update([
            'is_validated'    => true,
            'validated_by_id' => Auth::id(),
            'validation_date' => now(),
        ]);

        return response()->json($observation->load('validatedBy:id,name'));
    }

    // ── Moderation Endpoints (ported from Django ObservationAdmin) ──────

    /**
     * Bulk validate selected observations (staff only).
     *
     * POST /api/v1/observations/bulk-validate
     */
    public function bulkValidate(Request $request): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        $data = $request->validate([
            'observation_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'observation_ids.*' => ['integer', 'exists:observations,id'],
        ]);

        $count = Observation::whereIn('id', $data['observation_ids'])
            ->update([
                'is_validated'    => true,
                'validated_by_id' => Auth::id(),
                'validation_date' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$count} observation(s) validee(s).",
            'count'   => $count,
        ]);
    }

    /**
     * Bulk update visibility of selected observations (staff only).
     *
     * POST /api/v1/observations/bulk-visibility
     */
    public function bulkVisibility(Request $request): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        $data = $request->validate([
            'observation_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'observation_ids.*' => ['integer', 'exists:observations,id'],
            'is_public'         => ['required', 'boolean'],
        ]);

        $count = Observation::whereIn('id', $data['observation_ids'])
            ->update(['is_public' => $data['is_public']]);

        $status = $data['is_public'] ? 'publique(s)' : 'privee(s)';

        return response()->json([
            'success' => true,
            'message' => "{$count} observation(s) rendues {$status}.",
            'count'   => $count,
        ]);
    }
}
