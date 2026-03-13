<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Models\Plant;
use App\Models\PlantPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlantController extends Controller
{
    /**
     * Paginated list of plants with extensive filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Plant::with(
            'taxon:id,binomial_name,common_name_fr,genus,species,family',
            'category:id,name,category_type',
            'site:id,name',
            'owner:id,name'
        )
        ->withCount('observations', 'photos')
        ->addSelect([
            'last_observation_date' => Observation::select('observation_date')
                ->whereColumn('plant_id', 'plants.id')
                ->orderByDesc('observation_date')
                ->limit(1),
        ]);

        // --- Text search ---
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('taxon', function ($tq) use ($search) {
                      $tq->where('binomial_name', 'like', "%{$search}%")
                         ->orWhere('common_name_fr', 'like', "%{$search}%");
                  });
            });
        }

        // --- Direct filters ---
        if ($v = $request->query('category'))      $query->where('category_id', $v);
        if ($v = $request->query('site'))           $query->where('site_id', $v);
        if ($v = $request->query('health_status'))  $query->where('health_status', $v);
        if ($v = $request->query('height_category'))$query->where('height_category', $v);
        if ($v = $request->query('status'))         $query->where('status', $v);
        if ($v = $request->query('position'))       $query->where('position_id', $v);
        if ($v = $request->query('owner'))          $query->where('owner_id', $v);
        if ($v = $request->query('taxon'))          $query->where('taxon_id', $v);
        if ($v = $request->query('layer'))          $query->where('layer_id', $v);

        if ($request->has('is_private')) {
            $query->where('is_private', $request->boolean('is_private'));
        }

        // --- Multi-value filters ---
        if ($statusIn = $request->query('status_in')) {
            $query->whereIn('status', explode(',', $statusIn));
        }

        // --- Date ranges ---
        if ($v = $request->query('planting_date_from')) $query->where('planting_date', '>=', $v);
        if ($v = $request->query('planting_date_to'))   $query->where('planting_date', '<=', $v);
        if ($v = $request->query('death_date_from'))    $query->where('death_date', '>=', $v);
        if ($v = $request->query('death_date_to'))      $query->where('death_date', '<=', $v);

        // --- Count range filters (using having-style subqueries) ---
        if ($v = $request->query('observations_count_min')) {
            $query->has('observations', '>=', (int) $v);
        }
        if ($v = $request->query('observations_count_max')) {
            $query->has('observations', '<=', (int) $v);
        }
        if ($v = $request->query('photos_count_min')) {
            $query->has('photos', '>=', (int) $v);
        }
        if ($v = $request->query('photos_count_max')) {
            $query->has('photos', '<=', (int) $v);
        }

        // --- Boolean filters ---
        if ($request->has('has_observations')) {
            $request->boolean('has_observations')
                ? $query->has('observations')
                : $query->doesntHave('observations');
        }
        if ($request->has('has_photos')) {
            $request->boolean('has_photos')
                ? $query->has('photos')
                : $query->doesntHave('photos');
        }
        if ($request->has('has_position')) {
            $request->boolean('has_position')
                ? $query->whereNotNull('position_id')
                : $query->whereNull('position_id');
        }
        if ($request->has('is_in_succession')) {
            $request->boolean('is_in_succession')
                ? $query->whereNotNull('replaces_id')
                : $query->whereNull('replaces_id');
        }

        // --- Ordering ---
        $orderBy = $request->query('ordering', 'name');
        $direction = str_starts_with($orderBy, '-') ? 'desc' : 'asc';
        $column = ltrim($orderBy, '-');
        $query->orderBy($column, $direction);

        $perPage = min((int) ($request->query('per_page') ?? $request->query('page_size') ?? 20), 1000);

        $results = $query->paginate($perPage);

        // Add succession indicators (batch query to avoid N+1)
        $plantIds = $results->getCollection()->pluck('id')->toArray();
        $successorIds = Plant::whereIn('replaces_id', $plantIds)
            ->pluck('replaces_id')
            ->flip()
            ->toArray();

        $results->getCollection()->transform(function ($plant) use ($successorIds) {
            $plant->has_successor = isset($successorIds[$plant->id]);
            $plant->has_predecessor = $plant->replaces_id !== null;
            return $plant;
        });

        return response()->json($results);
    }

    /**
     * Show a single plant with full details.
     */
    public function show(int $id): JsonResponse
    {
        $plant = Plant::with(
            'taxon',
            'category:id,name,category_type',
            'site:id,name,latitude,longitude',
            'owner:id,name',
            'position:id,label,site_id'
        )
        ->withCount('observations', 'photos')
        ->findOrFail($id);

        $data = $plant->toArray();

        // Add last observation
        $lastObs = $plant->observations()
            ->with('phenologicalStage:id,stage_code,stage_description')
            ->orderByDesc('observation_date')
            ->first();
        $data['last_observation'] = $lastObs;

        // Add coordinates
        $data['coordinates'] = [
            'latitude'  => $plant->latitude,
            'longitude' => $plant->longitude,
        ];

        // Succession info (with taxon for display)
        $data['replaced_by'] = Plant::where('replaces_id', $plant->id)
            ->with('taxon:id,binomial_name,common_name_fr')
            ->select('id', 'name', 'status', 'taxon_id', 'planting_date', 'death_date', 'health_status')
            ->first();
        $data['replaces_plant'] = $plant->replaces_id
            ? Plant::with('taxon:id,binomial_name,common_name_fr')
                ->select('id', 'name', 'status', 'taxon_id', 'planting_date', 'death_date', 'death_cause', 'health_status')
                ->find($plant->replaces_id)
            : null;

        return response()->json($data);
    }

    /**
     * Create a new plant.
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'taxon_id'             => ['required', 'exists:taxons,id'],
            'category_id'          => ['required', 'exists:categories,id'],
            'site_id'              => ['required', 'exists:sites,id'],
            'position_id'          => ['nullable', 'exists:plant_positions,id'],
            'planting_date'        => ['nullable', 'date'],
            'age_years'            => ['nullable', 'integer', 'min:0'],
            'height_category'      => ['nullable', 'string', 'in:seedling,young,medium,mature,large'],
            'exact_height'         => ['nullable', 'numeric'],
            'health_status'        => ['nullable', 'string', 'in:excellent,good,fair,poor,dead'],
            'status'               => ['nullable', 'string', 'in:alive,dead,replaced,removed'],
            'clone_or_accession'   => ['nullable', 'string', 'max:100'],
            'is_private'           => ['nullable', 'boolean'],
            'latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'            => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy'         => ['nullable', 'numeric'],
            'map_position_x'       => ['nullable', 'numeric'],
            'map_position_y'       => ['nullable', 'numeric'],
            'layer_id'             => ['nullable', 'exists:site_plan_layers,id'],
            'notes'                => ['nullable', 'string'],
            'anecdotes'            => ['nullable', 'string'],
            'cultural_significance'=> ['nullable', 'string'],
            'ecological_notes'     => ['nullable', 'string'],
            'care_notes'           => ['nullable', 'string'],
            'replaces_id'          => ['nullable', 'exists:plants,id'],
        ]);

        $data['owner_id'] = Auth::id();
        $data['health_status'] = $data['health_status'] ?? 'good';
        $data['status'] = $data['status'] ?? 'alive';

        $plant = Plant::create($data);

        return response()->json(
            $plant->load('taxon:id,binomial_name,common_name_fr', 'category:id,name', 'site:id,name'),
            201
        );
    }

    /**
     * Update a plant (owner or staff only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $plant = Plant::findOrFail($id);

        if (Auth::id() !== $plant->owner_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'name'                 => ['sometimes', 'required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'taxon_id'             => ['sometimes', 'required', 'exists:taxons,id'],
            'category_id'          => ['sometimes', 'required', 'exists:categories,id'],
            'site_id'              => ['sometimes', 'required', 'exists:sites,id'],
            'position_id'          => ['nullable', 'exists:plant_positions,id'],
            'planting_date'        => ['nullable', 'date'],
            'age_years'            => ['nullable', 'integer', 'min:0'],
            'height_category'      => ['nullable', 'string', 'in:seedling,young,medium,mature,large'],
            'exact_height'         => ['nullable', 'numeric'],
            'health_status'        => ['nullable', 'string', 'in:excellent,good,fair,poor,dead'],
            'status'               => ['nullable', 'string', 'in:alive,dead,replaced,removed'],
            'clone_or_accession'   => ['nullable', 'string', 'max:100'],
            'is_private'           => ['nullable', 'boolean'],
            'latitude'             => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'            => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy'         => ['nullable', 'numeric'],
            'map_position_x'       => ['nullable', 'numeric'],
            'map_position_y'       => ['nullable', 'numeric'],
            'layer_id'             => ['nullable', 'exists:site_plan_layers,id'],
            'notes'                => ['nullable', 'string'],
            'anecdotes'            => ['nullable', 'string'],
            'cultural_significance'=> ['nullable', 'string'],
            'ecological_notes'     => ['nullable', 'string'],
            'care_notes'           => ['nullable', 'string'],
        ]);

        $plant->update($data);

        return response()->json($plant->load('taxon:id,binomial_name,common_name_fr', 'category:id,name', 'site:id,name'));
    }

    /**
     * Delete a plant (owner or staff only).
     */
    public function destroy(int $id): JsonResponse
    {
        $plant = Plant::findOrFail($id);

        if (Auth::id() !== $plant->owner_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $plant->delete();

        return response()->json(null, 204);
    }

    /**
     * Return plants owned by the current user.
     */
    public function myPlants(Request $request): JsonResponse
    {
        $plants = Plant::where('owner_id', Auth::id())
            ->with('taxon:id,binomial_name,common_name_fr', 'category:id,name', 'site:id,name')
            ->withCount('observations', 'photos')
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json($plants);
    }

    /**
     * Group plants by category.
     */
    public function byCategory(): JsonResponse
    {
        $plants = Plant::with('taxon:id,binomial_name,common_name_fr', 'category:id,name,category_type')
            ->withCount('observations')
            ->orderBy('name')
            ->get()
            ->groupBy('category_id');

        return response()->json($plants);
    }

    /**
     * Group plants by site.
     */
    public function bySite(): JsonResponse
    {
        $plants = Plant::with('taxon:id,binomial_name,common_name_fr', 'site:id,name')
            ->withCount('observations')
            ->orderBy('name')
            ->get()
            ->groupBy('site_id');

        return response()->json($plants);
    }

    /**
     * List observations for a specific plant.
     */
    public function observations(int $id): JsonResponse
    {
        $plant = Plant::findOrFail($id);

        $observations = $plant->observations()
            ->with('phenologicalStage:id,stage_code,stage_description,main_event_code', 'observer:id,name')
            ->withCount('photos')
            ->orderByDesc('observation_date')
            ->get();

        return response()->json($observations);
    }

    /**
     * List photos for a specific plant.
     */
    public function photos(int $id): JsonResponse
    {
        $plant = Plant::findOrFail($id);

        $photos = $plant->photos()
            ->with('photographer:id,name')
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($photos);
    }

    /**
     * Statistics for a specific plant.
     */
    public function statistics(int $id): JsonResponse
    {
        $plant = Plant::findOrFail($id);

        $observationsByStage = $plant->observations()
            ->join('phenological_stages', 'observations.phenological_stage_id', '=', 'phenological_stages.id')
            ->selectRaw('phenological_stages.stage_code, phenological_stages.stage_description, count(*) as count')
            ->groupBy('phenological_stages.stage_code', 'phenological_stages.stage_description')
            ->get();

        $yearExpr = match (DB::connection()->getDriverName()) {
            'sqlite'            => "CAST(strftime('%Y', observation_date) AS INTEGER)",
            'mysql', 'mariadb'  => 'YEAR(observation_date)',
            'pgsql'             => 'EXTRACT(YEAR FROM observation_date)::integer',
            default             => 'YEAR(observation_date)',
        };
        $observationsByYear = $plant->observations()
            ->selectRaw("{$yearExpr} as year, count(*) as count")
            ->groupByRaw($yearExpr)
            ->orderByRaw($yearExpr)
            ->get();

        return response()->json([
            'observations_count'    => $plant->observations()->count(),
            'photos_count'          => $plant->photos()->count(),
            'observations_by_stage' => $observationsByStage,
            'observations_by_year'  => $observationsByYear,
            'first_observation'     => $plant->observations()->orderBy('observation_date')->value('observation_date'),
            'last_observation'      => $plant->observations()->orderByDesc('observation_date')->value('observation_date'),
        ]);
    }

    /**
     * Plants with GPS coordinates for site mapping.
     */
    public function siteMap(Request $request): JsonResponse
    {
        $request->validate([
            'site_id'  => ['required', 'exists:sites,id'],
            'layer_id' => ['nullable', 'exists:site_plan_layers,id'],
        ]);

        $query = Plant::where('site_id', $request->query('site_id'))
            ->with('taxon:id,binomial_name,common_name_fr', 'category:id,name')
            ->select('id', 'name', 'latitude', 'longitude', 'taxon_id', 'category_id', 'site_id', 'status', 'health_status', 'map_position_x', 'map_position_y', 'layer_id');

        if ($layerId = $request->query('layer_id')) {
            $query->where('layer_id', $layerId);
        }

        // Include plants with GPS or map position
        $query->where(function ($q) {
            $q->whereNotNull('map_position_x')
              ->orWhere(function ($q2) {
                  $q2->whereNotNull('latitude')->whereNotNull('longitude');
              });
        });

        return response()->json($query->get());
    }

    /**
     * Find plants near given coordinates using the Haversine formula.
     */
    public function nearbyPlants(Request $request): JsonResponse
    {
        $request->validate([
            'lat'    => ['required', 'numeric', 'between:-90,90'],
            'lng'    => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $radiusMeters = $request->query('radius', 100);
        $radiusKm = $radiusMeters / 1000;

        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        $plants = Plant::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("*, {$haversine} AS distance_km", [$lat, $lng, $lat])
            ->whereRaw("{$haversine} < ?", [$lat, $lng, $lat, $radiusKm])
            ->orderByRaw("{$haversine} ASC", [$lat, $lng, $lat])
            ->with('taxon:id,binomial_name,common_name_fr', 'site:id,name')
            ->get();

        return response()->json($plants);
    }

    /**
     * Update GPS location for a plant.
     */
    public function updateGpsLocation(Request $request, int $id): JsonResponse
    {
        $plant = Plant::findOrFail($id);

        $data = $request->validate([
            'latitude'     => ['required', 'numeric', 'between:-90,90'],
            'longitude'    => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['nullable', 'numeric'],
        ]);

        $data['gps_recorded_at'] = now();

        $plant->update($data);

        return response()->json($plant);
    }

    /**
     * Export plants with their observations.
     */
    public function exportWithObservations(Request $request): JsonResponse
    {
        $query = Plant::with([
            'taxon:id,binomial_name,common_name_fr,family,genus,species',
            'category:id,name',
            'site:id,name',
            'observations' => fn ($q) => $q->with('phenologicalStage:id,stage_code,stage_description')
                ->orderByDesc('observation_date'),
        ])->withCount('observations', 'photos');

        if ($siteId = $request->query('site_id')) {
            $query->where('site_id', $siteId);
        }

        return response()->json($query->get());
    }

    /**
     * Mark a plant as dead.
     *
     * POST /api/v1/plants/{id}/mark-dead
     */
    public function markDead(Request $request, int $id): JsonResponse
    {
        $plant = Plant::findOrFail($id);

        // Permission: owner or staff only
        if (Auth::id() !== $plant->owner_id && ! Auth::user()->is_staff) {
            return response()->json(
                ['error' => 'Vous n\'avez pas la permission de modifier cette plante'],
                403
            );
        }

        // Cannot mark already dead/replaced plants
        if (in_array($plant->status, ['dead', 'replaced'])) {
            $label = $plant->status === 'dead' ? 'morte' : 'remplacée';
            return response()->json(
                ['error' => "Cette plante est déjà {$label}"],
                400
            );
        }

        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'death_date'  => ['required', 'date'],
            'death_cause' => ['nullable', 'string', 'in:disease,pests,frost,drought,flooding,wind,age,accident,human,unknown,other'],
            'death_notes' => ['nullable', 'string'],
        ]);

        $plant->update([
            'status'        => 'dead',
            'health_status' => 'dead',
            'death_date'    => $data['death_date'],
            'death_cause'   => $data['death_cause'] ?? null,
            'death_notes'   => $data['death_notes'] ?? null,
        ]);

        $plant->load('taxon:id,binomial_name,common_name_fr', 'category:id,name', 'site:id,name', 'owner:id,name,email');

        return response()->json([
            'message' => "Plante \"{$plant->name}\" marquée comme morte",
            'plant'   => $plant,
        ]);
    }

    /**
     * Replace a dead/alive plant with a new plant at the same position.
     *
     * POST /api/v1/plants/{id}/replace
     * Body: { new_plant: { name, taxon, category, planting_date, ... } }
     */
    public function replace(Request $request, int $id): JsonResponse
    {
        $oldPlant = Plant::findOrFail($id);

        // Permission: owner or staff only
        if (Auth::id() !== $oldPlant->owner_id && ! Auth::user()->is_staff) {
            return response()->json(
                ['error' => 'Vous n\'avez pas la permission de modifier cette plante'],
                403
            );
        }

        // Cannot replace a plant that was already replaced
        if ($oldPlant->status === 'replaced') {
            return response()->json(
                ['error' => 'Cette plante a déjà été remplacée'],
                400
            );
        }

        // Extract new_plant data from nested structure (frontend sends { new_plant: {...} })
        $newPlantData = $request->input('new_plant', []);
        if (empty($newPlantData) || ! is_array($newPlantData)) {
            return response()->json(
                ['error' => 'Les données de la nouvelle plante sont requises (new_plant)'],
                400
            );
        }

        // Validate new plant data
        $validator = validator($newPlantData, [
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'taxon'           => ['required', 'exists:taxons,id'],
            'category'        => ['required', 'exists:categories,id'],
            'planting_date'   => ['nullable', 'date'],
            'health_status'   => ['nullable', 'string', 'in:excellent,good,fair,poor'],
            'height_category' => ['nullable', 'string', 'in:seedling,young,medium,mature,large'],
            'notes'           => ['nullable', 'string'],
            'is_private'      => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['error' => $validator->errors()->first()],
                422
            );
        }

        $validated = $validator->validated();

        // If plant is alive, mark it dead first (death_date from request or today)
        if ($oldPlant->status === 'alive') {
            $oldPlant->death_date = $request->input('death_date', now()->toDateString());
            $oldPlant->death_cause = $request->input('death_cause', '');
            $oldPlant->death_notes = $request->input('death_notes', '');
        }

        try {
            $newPlant = DB::transaction(function () use ($oldPlant, $validated) {
                // Step 1: Mark old plant as replaced
                $oldPlant->status = 'replaced';
                $oldPlant->save();

                // Step 2: Create new plant inheriting position/site/GPS
                return Plant::create([
                    'name'           => $validated['name'],
                    'description'    => $validated['description'] ?? null,
                    'taxon_id'       => $validated['taxon'],
                    'category_id'    => $validated['category'],
                    'planting_date'  => $validated['planting_date'] ?? null,
                    'health_status'  => $validated['health_status'] ?? 'good',
                    'notes'          => $validated['notes'] ?? null,
                    'is_private'     => $validated['is_private'] ?? $oldPlant->is_private,
                    // Inherited from old plant
                    'site_id'        => $oldPlant->site_id,
                    'position_id'    => $oldPlant->position_id,
                    'owner_id'       => $oldPlant->owner_id,
                    'latitude'       => $oldPlant->latitude,
                    'longitude'      => $oldPlant->longitude,
                    'map_position_x' => $oldPlant->map_position_x,
                    'map_position_y' => $oldPlant->map_position_y,
                    'layer_id'       => $oldPlant->layer_id,
                    // Succession link
                    'replaces_id'    => $oldPlant->id,
                    'status'         => 'alive',
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Erreur lors du remplacement: ' . $e->getMessage()],
                500
            );
        }

        $newPlant->load('taxon:id,binomial_name,common_name_fr', 'category:id,name', 'site:id,name');

        return response()->json([
            'message'   => "Plante remplacée avec succès. Nouvelle plante: {$newPlant->name}",
            'old_plant' => [
                'id'         => $oldPlant->id,
                'name'       => $oldPlant->name,
                'status'     => $oldPlant->status,
                'death_date' => $oldPlant->death_date?->toDateString(),
            ],
            'new_plant' => $newPlant,
            'position'  => $oldPlant->position ? [
                'id'    => $oldPlant->position->id,
                'label' => $oldPlant->position->label,
            ] : null,
        ], 201);
    }

    /**
     * Bulk update map positions for multiple plants.
     */
    public function bulkUpdateMapPositions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_id'                    => ['nullable', 'exists:sites,id'],
            'layer_id'                   => ['nullable', 'exists:site_plan_layers,id'],
            'positions'                  => ['required', 'array'],
            'positions.*.plant_id'       => ['required', 'exists:plants,id'],
            'positions.*.map_position_x' => ['required', 'numeric', 'between:0,100'],
            'positions.*.map_position_y' => ['required', 'numeric', 'between:0,100'],
        ]);

        $globalLayerId = $data['layer_id'] ?? null;
        $count = 0;

        DB::transaction(function () use ($data, $globalLayerId, &$count) {
            foreach ($data['positions'] as $pos) {
                $updateData = [
                    'map_position_x' => $pos['map_position_x'],
                    'map_position_y' => $pos['map_position_y'],
                ];
                if ($globalLayerId !== null) {
                    $updateData['layer_id'] = $globalLayerId;
                }
                Plant::where('id', $pos['plant_id'])->update($updateData);
                $count++;
            }
        });

        return response()->json([
            'detail' => 'Positions mises a jour.',
            'updated_count' => $count,
        ]);
    }
}
