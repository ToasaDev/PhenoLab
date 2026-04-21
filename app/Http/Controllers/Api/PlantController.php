<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Models\Plant;
use App\Models\PlantPosition;
use App\Models\PlantPhoto;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SitePlanLayer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlantController extends Controller
{
    use Concerns\SanitizesOrdering;

    private function canManagePlant(Plant $plant): bool
    {
        $user = Auth::user();
        if ($user === null) return false;
        if ($user->is_staff) return true;
        if ($plant->owner_id === $user->id) return true;
        // Group members can manage shared plants
        if ($plant->group_id && in_array($plant->group_id, $user->groupIds())) return true;

        return false;
    }

    private function visiblePlantsQuery(): Builder
    {
        $query = Plant::query();
        $user = Auth::user();

        if ($user?->is_staff) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($user) {
            $visible->where(function (Builder $public) {
                $public->where('is_private', false)
                    ->whereHas('site', fn (Builder $site) => $site->where('is_private', false));
            });

            if ($user !== null) {
                $visible->orWhere('owner_id', $user->id);
                // Include plants shared with user's groups
                $groupIds = $user->groupIds();
                if (!empty($groupIds)) {
                    $visible->orWhereIn('group_id', $groupIds);
                }
            }
        });
    }

    private function visibleObservationsForPlant(Plant $plant): Builder|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        $query = $plant->observations()->with('phenologicalStage:id,stage_code,stage_description,main_event_code', 'observer:id,name');
        $user = Auth::user();

        if ($user?->is_staff || $plant->owner_id === $user?->id) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($user) {
            $visible->where('is_public', true);

            if ($user !== null) {
                $visible->orWhere('observer_id', $user->id);
            }
        });
    }

    private function visiblePhotosForPlant(Plant $plant): Builder|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        $query = $plant->photos()->with('photographer:id,name');
        $user = Auth::user();

        if ($user?->is_staff || $plant->owner_id === $user?->id) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($user) {
            $visible->where('is_public', true);

            if ($user !== null) {
                $visible->orWhere('photographer_id', $user->id);
            }
        });
    }

    private function authorizePlantRelations(array $data, ?Plant $existingPlant = null): ?JsonResponse
    {
        $user = Auth::user();
        $targetSiteId = $data['site_id'] ?? $existingPlant?->site_id;

        if ($targetSiteId === null) {
            return null;
        }

        $site = Site::findOrFail($targetSiteId);

        if (! $user?->is_staff && (int) $site->owner_id !== (int) $user?->id) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        if (array_key_exists('position_id', $data) && $data['position_id'] !== null) {
            $position = PlantPosition::findOrFail($data['position_id']);

            if ((int) $position->site_id !== (int) $targetSiteId) {
                return response()->json([
                    'errors' => ['position_id' => ['La position doit appartenir au meme site que la plante.']],
                ], 422);
            }
        }

        if (array_key_exists('layer_id', $data) && $data['layer_id'] !== null) {
            $layer = SitePlanLayer::findOrFail($data['layer_id']);

            if ((int) $layer->site_id !== (int) $targetSiteId) {
                return response()->json([
                    'errors' => ['layer_id' => ['Le calque doit appartenir au meme site que la plante.']],
                ], 422);
            }
        }

        if (array_key_exists('replaces_id', $data) && $data['replaces_id'] !== null) {
            $replacedPlant = Plant::findOrFail($data['replaces_id']);

            if ((int) $replacedPlant->site_id !== (int) $targetSiteId) {
                return response()->json([
                    'errors' => ['replaces_id' => ['La plante remplacee doit appartenir au meme site.']],
                ], 422);
            }
        }

        return null;
    }

    /**
     * Paginated list of plants with extensive filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->visiblePlantsQuery()->with(
            'taxon:id,binomial_name,common_name_fr,genus,species,family',
            'category:id,name,icon,category_type',
            'site:id,name',
            'owner:id,name',
            'mainPhoto:id,plant_id,image,is_main_photo'
        )
        ->withCount('observations', 'photos', 'actions')
        ->addSelect([
            'last_observation_date' => Observation::select('observation_date')
                ->whereColumn('plant_id', 'plants.id')
                ->orderByDesc('observation_date')
                ->limit(1),
            'last_action_date' => \App\Models\PlantAction::select('action_date')
                ->whereColumn('plant_id', 'plants.id')
                ->orderByDesc('action_date')
                ->limit(1),
        ]);

        // --- Text search ---
        if ($search = $request->query('search')) {
            $search = $this->escapeLike($search);
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
        if ($v = $request->query('site_category_id')) {
            $category = SiteCategory::with('children.children.children')->find((int) $v);
            $ids      = $category ? $category->descendantIds() : [(int) $v];
            $query->whereHas('site', fn (Builder $s) => $s->whereIn('site_category_id', $ids));
        }

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
        if ($request->has('has_actions')) {
            $request->boolean('has_actions')
                ? $query->has('actions')
                : $query->doesntHave('actions');
        }
        if ($request->filled('action_type_id')) {
            $query->whereHas('actions', fn (Builder $a) => $a->where('action_type_id', $request->integer('action_type_id')));
        }

        // --- Cultivation profile filters ---
        if ($v = $request->query('cultivation_exposure')) {
            $query->whereHas('cultivationProfile', fn (Builder $cp) => $cp->where('exposure', $v));
        }
        if ($v = $request->query('cultivation_difficulty')) {
            $query->whereHas('cultivationProfile', fn (Builder $cp) => $cp->where('cultivation_difficulty', $v));
        }
        if ($v = $request->query('cultivation_usda_zone')) {
            $query->whereHas('cultivationProfile', fn (Builder $cp) => $cp->where('usda_zone', $v));
        }
        if ($v = $request->query('cultivation_suitable_environment')) {
            $query->whereHas('cultivationProfile', fn (Builder $cp) => $cp->where('suitable_environments', 'like', '%"'.$v.'"%'));
        }
        if ($v = $request->query('cultivation_usage_type')) {
            $query->whereHas('cultivationProfile', fn (Builder $cp) => $cp->where('usage_types', 'like', '%"'.$v.'"%'));
        }

        // --- Tag filter ---
        if ($tagId = $request->query('tag_id')) {
            $query->whereHas('userTags', fn (Builder $tq) => $tq->where('user_plant_tags.id', $tagId));
        }

        // --- Group filter ---
        if ($groupId = $request->query('group_id')) {
            $query->where('group_id', $groupId);
        }

        // --- Ordering ---
        [$column, $direction] = $this->parseOrdering(
            $request->query('ordering', 'name'),
            ['name', 'created_at', 'planting_date', 'health_status', 'status', 'id', 'actions_count'],
            'name'
        );
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
        $plant = $this->visiblePlantsQuery()->with(
            'taxon',
            'category:id,name,icon,category_type',
            'site:id,name,latitude,longitude',
            'owner:id,name',
            'position:id,label,site_id',
            'cultivationProfile'
        )
        ->withCount('observations', 'photos', 'actions')
        ->findOrFail($id);

        $data = $plant->toArray();
        $data['cultivation_profile'] = $plant->cultivationProfile;

        // Add last action summary
        $lastAction = $plant->actions()
            ->with('actionType:id,name,slug,icon,color')
            ->orderByDesc('action_date')
            ->first();
        $data['last_action'] = $lastAction ? [
            'id'          => $lastAction->id,
            'action_date' => $lastAction->action_date->format('Y-m-d'),
            'type'        => $lastAction->actionType?->name,
            'icon'        => $lastAction->actionType?->icon,
            'color'       => $lastAction->actionType?->color,
        ] : null;

        // Add last observation
        $lastObs = $this->visibleObservationsForPlant($plant)
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
        $data['replaced_by'] = $this->visiblePlantsQuery()->where('replaces_id', $plant->id)
            ->with('taxon:id,binomial_name,common_name_fr')
            ->select('id', 'name', 'status', 'taxon_id', 'planting_date', 'death_date', 'health_status')
            ->first();
        $data['replaces_plant'] = $plant->replaces_id
            ? $this->visiblePlantsQuery()->with('taxon:id,binomial_name,common_name_fr')
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
        $lockKey = 'plant_store_' . Auth::id();
        if (! Cache::lock($lockKey, 5)->get()) {
            return response()->json(['message' => 'Requête déjà en cours, veuillez patienter.'], 429);
        }

        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'taxon_id'             => ['required', 'exists:taxons,id'],
            'category_id'          => ['required', 'exists:categories,id'],
            'site_id'              => ['required', 'exists:sites,id'],
            'position_id'          => ['nullable', 'exists:plant_positions,id'],
            'planting_date'        => ['nullable', 'date_format:Y-m-d'],
            'age_years'            => ['nullable', 'integer', 'min:0'],
            'height_category'      => ['nullable', 'string', 'in:seedling,young,medium,mature,large'],
            'exact_height'         => ['nullable', 'numeric'],
            'abundance'            => ['nullable', 'integer', 'min:1'],
            'initial_abundance'    => ['nullable', 'integer', 'min:1'],
            'health_status'             => ['nullable', 'string', 'in:excellent,good,fair,poor,dead'],
            'identification_certainty'  => ['nullable', 'string', 'in:certain,uncertain,undetermined'],
            'status'               => ['nullable', 'string', 'in:alive,dead,replaced,removed'],
            'clone_or_accession'   => ['nullable', 'string', 'max:100'],
            'cultivar'             => ['nullable', 'string', 'max:100'],
            'variety'              => ['nullable', 'string', 'max:100'],
            'cultivar_info'        => ['nullable', 'array'],
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
            'group_id'             => ['nullable', 'exists:user_groups,id'],
        ]);

        if ($response = $this->authorizePlantRelations($data)) {
            return $response;
        }

        // Verify user belongs to the group if specified
        if (!empty($data['group_id'])) {
            $groupIds = Auth::user()->groupIds();
            if (!in_array((int) $data['group_id'], $groupIds) && !Auth::user()->is_staff) {
                return response()->json(['message' => 'Vous ne faites pas partie de ce groupe.'], 403);
            }
        }

        $data['owner_id'] = Auth::id();
        $data['health_status'] = $data['health_status'] ?? 'good';
        $data['status'] = $data['status'] ?? 'alive';

        $plant = Plant::create($data);

        return response()->json(
            $plant->load('taxon:id,binomial_name,common_name_fr', 'category:id,name,icon,category_type', 'site:id,name'),
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
            'planting_date'        => ['nullable', 'date_format:Y-m-d'],
            'age_years'            => ['nullable', 'integer', 'min:0'],
            'height_category'      => ['nullable', 'string', 'in:seedling,young,medium,mature,large'],
            'exact_height'         => ['nullable', 'numeric'],
            'abundance'            => ['nullable', 'integer', 'min:1'],
            'initial_abundance'    => ['nullable', 'integer', 'min:1'],
            'health_status'             => ['nullable', 'string', 'in:excellent,good,fair,poor,dead'],
            'identification_certainty'  => ['nullable', 'string', 'in:certain,uncertain,undetermined'],
            'status'               => ['nullable', 'string', 'in:alive,dead,replaced,removed'],
            'clone_or_accession'   => ['nullable', 'string', 'max:100'],
            'cultivar'             => ['nullable', 'string', 'max:100'],
            'variety'              => ['nullable', 'string', 'max:100'],
            'cultivar_info'        => ['nullable', 'array'],
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

        if ($response = $this->authorizePlantRelations($data, $plant)) {
            return $response;
        }

        $plant->update($data);

        return response()->json($plant->load('taxon:id,binomial_name,common_name_fr', 'category:id,name,icon,category_type', 'site:id,name'));
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
            ->with('taxon:id,binomial_name,common_name_fr', 'category:id,name,icon,category_type', 'site:id,name', 'mainPhoto:id,plant_id,image,is_main_photo')
            ->withCount('observations', 'photos')
            ->orderBy('name')
            ->paginate(min((int) ($request->query('per_page') ?? $request->query('page_size') ?? 20), 100));

        return response()->json($plants);
    }

    /**
     * Group plants by category.
     */
    public function byCategory(): JsonResponse
    {
        $plants = $this->visiblePlantsQuery()
            ->with('taxon:id,binomial_name,common_name_fr', 'category:id,name,icon,category_type')
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
        $plants = $this->visiblePlantsQuery()
            ->with('taxon:id,binomial_name,common_name_fr', 'site:id,name')
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
        $plant = $this->visiblePlantsQuery()->findOrFail($id);

        $observations = $this->visibleObservationsForPlant($plant)
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
        $plant = $this->visiblePlantsQuery()->findOrFail($id);

        $photos = $this->visiblePhotosForPlant($plant)
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
        $plant = $this->visiblePlantsQuery()->findOrFail($id);
        $visibleObservations = $this->visibleObservationsForPlant($plant);

        $observationsByStage = (clone $visibleObservations)
            ->join('phenological_stages', 'observations.phenological_stage_id', '=', 'phenological_stages.id')
            ->selectRaw('phenological_stages.id as stage_id, phenological_stages.stage_code, phenological_stages.stage_description, count(*) as count, AVG(observations.intensity) as avg_intensity')
            ->groupBy('phenological_stages.id', 'phenological_stages.stage_code', 'phenological_stages.stage_description')
            ->orderBy('phenological_stages.stage_code')
            ->get();

        $yearExpr = match (DB::connection()->getDriverName()) {
            'sqlite'            => "CAST(strftime('%Y', observation_date) AS INTEGER)",
            'mysql', 'mariadb'  => 'YEAR(observation_date)',
            'pgsql'             => 'EXTRACT(YEAR FROM observation_date)::integer',
            default             => 'YEAR(observation_date)',
        };
        $observationsByYear = (clone $visibleObservations)
            ->selectRaw("{$yearExpr} as year, count(*) as count")
            ->groupByRaw($yearExpr)
            ->orderByRaw($yearExpr)
            ->get();

        // Phenological calendar: first/last observation date per stage
        $phenologicalCalendar = (clone $visibleObservations)
            ->join('phenological_stages', 'observations.phenological_stage_id', '=', 'phenological_stages.id')
            ->selectRaw('phenological_stages.stage_code, phenological_stages.stage_description, MIN(observation_date) as first_date, MAX(observation_date) as last_date, count(*) as count')
            ->groupBy('phenological_stages.stage_code', 'phenological_stages.stage_description')
            ->orderBy('phenological_stages.stage_code')
            ->get()
            ->map(function ($row) {
                $first = $row->first_date ? \Carbon\Carbon::parse($row->first_date) : null;
                $last  = $row->last_date  ? \Carbon\Carbon::parse($row->last_date)  : null;
                return [
                    'stage_code'        => $row->stage_code,
                    'stage_description' => $row->stage_description,
                    'first_date'        => $row->first_date,
                    'last_date'         => $row->last_date,
                    'count'             => (int) $row->count,
                    'duration_days'     => ($first && $last) ? $first->diffInDays($last) + 1 : null,
                ];
            });

        // Interannual comparison: first observation date for each stage, per year
        $interannualByStage = (clone $visibleObservations)
            ->join('phenological_stages', 'observations.phenological_stage_id', '=', 'phenological_stages.id')
            ->selectRaw("phenological_stages.stage_code, phenological_stages.stage_description, {$yearExpr} as year, MIN(observation_date) as first_date, MIN(day_of_year) as first_doy")
            ->groupByRaw("phenological_stages.stage_code, phenological_stages.stage_description, {$yearExpr}")
            ->orderBy('phenological_stages.stage_code')
            ->orderByRaw($yearExpr)
            ->get()
            ->groupBy('stage_code')
            ->map(function ($rows, $stageCode) {
                return [
                    'stage_code'        => $stageCode,
                    'stage_description' => $rows->first()->stage_description,
                    'years'             => $rows->map(fn ($r) => [
                        'year'       => (int) $r->year,
                        'first_date' => $r->first_date,
                        'day_of_year' => $r->first_doy ? (int) $r->first_doy : null,
                    ])->values(),
                ];
            })
            ->values();

        // Weather averages
        $weather = (clone $visibleObservations)
            ->selectRaw('AVG(temperature) as avg_temperature, AVG(humidity) as avg_humidity, AVG(wind_speed) as avg_wind_speed')
            ->first();

        $weatherConditions = (clone $visibleObservations)
            ->whereNotNull('weather_condition')
            ->selectRaw('weather_condition, count(*) as count')
            ->groupBy('weather_condition')
            ->orderByDesc('count')
            ->get();

        // Recent activity (last 30 days)
        $thirtyDaysAgo = now()->subDays(30)->toDateString();
        $recentObservationsCount = (clone $visibleObservations)
            ->where('observation_date', '>=', $thirtyDaysAgo)
            ->count();

        $lastPhoto = $this->visiblePhotosForPlant($plant)
            ->orderByDesc('created_at')
            ->select('id', 'title', 'created_at', 'photo_type')
            ->first();

        // Distinct observers count
        $distinctObserversCount = (clone $visibleObservations)
            ->distinct('observer_id')
            ->count('observer_id');

        return response()->json([
            'observations_count'       => (clone $visibleObservations)->count(),
            'distinct_observers_count' => $distinctObserversCount,
            'photos_count'             => $this->visiblePhotosForPlant($plant)->count(),
            'observations_by_stage'    => $observationsByStage,
            'observations_by_year'     => $observationsByYear,
            'phenological_calendar'    => $phenologicalCalendar,
            'interannual_by_stage'     => $interannualByStage,
            'weather'                  => [
                'avg_temperature' => $weather?->avg_temperature ? round((float) $weather->avg_temperature, 1) : null,
                'avg_humidity'    => $weather?->avg_humidity ? round((float) $weather->avg_humidity, 1) : null,
                'avg_wind_speed'  => $weather?->avg_wind_speed ? round((float) $weather->avg_wind_speed, 1) : null,
                'conditions'      => $weatherConditions,
            ],
            'recent_activity' => [
                'observations_last_30_days' => $recentObservationsCount,
                'last_photo'                => $lastPhoto,
            ],
            'first_observation' => (clone $visibleObservations)->orderBy('observation_date')->value('observation_date'),
            'last_observation'  => (clone $visibleObservations)->orderByDesc('observation_date')->value('observation_date'),
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

        $siteId = $request->query('site_id');
        $layerId = $request->query('layer_id');

        // Resolve effective layer: explicit param, otherwise active layer for the site.
        if (! $layerId) {
            $layerId = SitePlanLayer::where('site_id', $siteId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->value('id');
        }

        $query = $this->visiblePlantsQuery()
            ->where('site_id', $siteId)
            ->with('taxon:id,binomial_name,common_name_fr', 'category:id,name,icon,category_type', 'mainPhoto:id,plant_id,image,is_main_photo')
            ->select('id', 'name', 'latitude', 'longitude', 'taxon_id', 'category_id', 'site_id', 'status', 'health_status', 'map_position_x', 'map_position_y', 'layer_id');

        $plants = $query->get();

        // Overlay layer-specific positions from the pivot (source of truth per layer).
        if ($layerId) {
            $positions = \App\Models\PlantLayerPosition::where('layer_id', $layerId)
                ->whereIn('plant_id', $plants->pluck('id'))
                ->get()
                ->keyBy('plant_id');

            $plants = $plants->map(function ($p) use ($positions, $layerId) {
                $pos = $positions->get($p->id);
                $p->map_position_x = $pos?->map_position_x;
                $p->map_position_y = $pos?->map_position_y;
                $p->layer_id = $pos ? $layerId : null;
                return $p;
            });
        }

        // Filter to plants visible on this layer (positioned) or with GPS fallback.
        $plants = $plants->filter(function ($p) {
            return $p->map_position_x !== null
                || ($p->latitude !== null && $p->longitude !== null);
        })->values();

        $site = \App\Models\Site::select('id', 'name', 'latitude', 'longitude', 'description')
            ->find($request->query('site_id'));

        $plantsWithGps = $plants->filter(fn ($p) => $p->latitude !== null && $p->longitude !== null)->count();

        return response()->json([
            'site'                => $site,
            'plants'              => $plants,
            'total_plants'        => $plants->count(),
            'plants_without_gps'  => $plants->count() - $plantsWithGps,
        ]);
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

        $plants = $this->visiblePlantsQuery()
            ->whereNotNull('latitude')
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

        if (! $this->canManagePlant($plant)) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

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
        $user = Auth::user();

        $query = $this->visiblePlantsQuery()->with([
            'taxon:id,binomial_name,common_name_fr,family,genus,species',
            'category:id,name,icon,category_type',
            'site:id,name',
            'observations' => function ($q) use ($user) {
                $q->with('phenologicalStage:id,stage_code,stage_description')
                    ->when(! $user?->is_staff, function ($visible) use ($user) {
                        $visible->where(function ($scope) use ($user) {
                            $scope->where('is_public', true);

                            if ($user !== null) {
                                $scope->orWhere('observer_id', $user->id);
                            }
                        });
                    })
                    ->orderByDesc('observation_date');
            },
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
            'death_date'  => ['required', 'date_format:Y-m-d'],
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

        $plant->load('taxon:id,binomial_name,common_name_fr', 'category:id,name,icon,category_type', 'site:id,name', 'owner:id,name');

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

        $newPlant->load('taxon:id,binomial_name,common_name_fr', 'category:id,name,icon,category_type', 'site:id,name');

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

        $user = Auth::user();
        $plantIds = collect($data['positions'])->pluck('plant_id')->unique()->values();
        $plants = Plant::whereIn('id', $plantIds)->get(['id', 'owner_id', 'site_id'])->keyBy('id');

        if ($plants->count() !== $plantIds->count()) {
            return response()->json(['detail' => 'Plante introuvable.'], 404);
        }

        if (! $user?->is_staff) {
            $hasUnauthorizedPlant = $plants->contains(
                fn (Plant $plant) => (int) $plant->owner_id !== (int) $user?->id
            );

            if ($hasUnauthorizedPlant) {
                return response()->json(['detail' => 'Non autorise.'], 403);
            }
        }

        if (isset($data['site_id'])) {
            $siteMismatch = $plants->contains(
                fn (Plant $plant) => (int) $plant->site_id !== (int) $data['site_id']
            );

            if ($siteMismatch) {
                return response()->json([
                    'errors' => ['site_id' => ['Toutes les plantes doivent appartenir au site fourni.']],
                ], 422);
            }
        }

        $globalLayerId = $data['layer_id'] ?? null;

        if ($globalLayerId !== null) {
            $layer = SitePlanLayer::findOrFail($globalLayerId);
            $layerMismatch = $plants->contains(
                fn (Plant $plant) => (int) $plant->site_id !== (int) $layer->site_id
            );

            if ($layerMismatch) {
                return response()->json([
                    'errors' => ['layer_id' => ['Le calque doit appartenir au meme site que chaque plante.']],
                ], 422);
            }
        }

        if ($globalLayerId === null) {
            return response()->json([
                'errors' => ['layer_id' => ['Un calque est requis pour enregistrer les positions.']],
            ], 422);
        }

        // Determine if this layer is the currently active layer for its site,
        // so we can also sync the cache columns on plants.
        $layer = $layer ?? SitePlanLayer::findOrFail($globalLayerId);
        $isActiveLayer = (bool) $layer->is_active;

        $count = 0;

        DB::transaction(function () use ($data, $globalLayerId, $isActiveLayer, &$count) {
            foreach ($data['positions'] as $pos) {
                \App\Models\PlantLayerPosition::updateOrCreate(
                    ['layer_id' => $globalLayerId, 'plant_id' => $pos['plant_id']],
                    [
                        'map_position_x' => $pos['map_position_x'],
                        'map_position_y' => $pos['map_position_y'],
                    ],
                );

                if ($isActiveLayer) {
                    Plant::where('id', $pos['plant_id'])->update([
                        'map_position_x' => $pos['map_position_x'],
                        'map_position_y' => $pos['map_position_y'],
                        'layer_id'       => $globalLayerId,
                    ]);
                }

                $count++;
            }
        });

        return response()->json([
            'detail' => 'Positions mises a jour.',
            'updated_count' => $count,
        ]);
    }

    /**
     * Search Wikidata for cultivar/variety information.
     * Uses SPARQL for broad search linked to a species, with entity API fallback.
     */
    public function searchCultivars(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:100'],
            'species' => ['nullable', 'string', 'max:100'],
        ]);

        $query = $request->input('query');
        $species = $request->input('species');

        // 1. Local DB search (EUPVP + manual entries)
        $localQuery = \App\Models\Cultivar::with('taxon:id,binomial_name,common_name_fr')
            ->search($query);

        if ($species) {
            $localQuery->whereHas('taxon', function ($tq) use ($species) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $species);
                $tq->where('binomial_name', 'like', "%{$escaped}%");
            });
        }

        $localResults = $localQuery->limit(15)->get()->map(fn (\App\Models\Cultivar $c) => [
            'name'            => $c->name,
            'cultivar_name'   => $c->name,
            'taxon_name'      => $c->taxon?->binomial_name,
            'common_name'     => $c->taxon?->common_name_fr,
            'description'     => $c->synonyms ? "Syn: {$c->synonyms}" : null,
            'image_url'       => $c->image_url,
            'origin'          => $c->origin_country,
            'date'            => $c->registration_date?->format('Y'),
            'wikidata_id'     => $c->wikidata_id,
            'cultivar_id'     => $c->id,
            'source'          => 'local',
        ])->toArray();

        // 2. Wikidata search (complementary)
        $wikidataResults = [];
        $ua = 'PhenoLab/1.0 (botanical garden app; contact@phenolab.org)';

        try {
            $sparqlResults = $this->searchCultivarsSparql($query, $species, $ua);
            if (! empty($sparqlResults)) {
                $wikidataResults = $sparqlResults;
            }

            if (empty($wikidataResults)) {
                $wikidataResults = $this->searchCultivarsEntityApi($query, $species, $ua);
            }

            // Mark Wikidata results with source
            foreach ($wikidataResults as &$r) {
                $r['source'] = 'wikidata';
                $r['cultivar_id'] = null;
            }
            unset($r);
        } catch (\Exception $e) {
            // Wikidata failure is not critical — we still have local results
        }

        // 3. Merge: local first, then Wikidata (deduplicate by name)
        $seenNames = collect($localResults)->pluck('name')->map(fn ($n) => strtolower($n))->toArray();
        $filtered = array_filter($wikidataResults, function ($r) use ($seenNames) {
            $name = strtolower($r['cultivar_name'] ?? $r['name'] ?? '');
            return ! in_array($name, $seenNames);
        });

        $results = array_merge($localResults, array_values($filtered));

        return response()->json(['results' => array_slice($results, 0, 30)]);
    }

    /**
     * SPARQL-based cultivar search — works with partial names and species filter.
     */
    private function searchCultivarsSparql(string $query, ?string $species, string $ua): array
    {
        $queryLower = strtolower($query);

        $sparql = <<<SPARQL
SELECT DISTINCT ?item ?itemLabel ?taxonName ?image ?desc ?originLabel ?date WHERE {
  { ?item wdt:P31 wd:Q4886. } UNION { ?item wdt:P31 wd:Q15731356. }
  OPTIONAL { ?item wdt:P225 ?taxonName. }
  OPTIONAL { ?item wdt:P18 ?image. }
  OPTIONAL { ?item schema:description ?desc. FILTER(LANG(?desc) = 'fr') }
  OPTIONAL { ?item wdt:P495 ?origin. }
  OPTIONAL { ?item wdt:P575 ?date. }
  FILTER(
    CONTAINS(LCASE(?itemLabel), '{$queryLower}')
    || CONTAINS(LCASE(COALESCE(?taxonName, '')), '{$queryLower}')
  )
  SERVICE wikibase:label { bd:serviceParam wikibase:language 'fr,en'. }
}
LIMIT 30
SPARQL;

        $response = \Illuminate\Support\Facades\Http::timeout(15)
            ->withUserAgent($ua)
            ->get('https://query.wikidata.org/sparql', [
                'format' => 'json',
                'query'  => $sparql,
            ]);

        $results = [];
        $seen = [];
        foreach ($response->json('results.bindings', []) as $r) {
            $wikidataId = basename($r['item']['value'] ?? '');
            if (isset($seen[$wikidataId])) {
                continue;
            }
            $seen[$wikidataId] = true;

            $label = $r['itemLabel']['value'] ?? '?';
            $taxonName = $r['taxonName']['value'] ?? null;
            $desc = $r['desc']['value'] ?? '';

            // Filter by species: check taxon_name, description, or common species words
            if ($species) {
                $speciesLower = strtolower($species);
                // Extract genus (first word) for broader matching
                $genus = explode(' ', $speciesLower)[0];
                // Extract common name from description (e.g. "variété de pomme" -> pomme)
                $speciesKeywords = $this->getSpeciesKeywords($species);

                $allText = strtolower(($taxonName ?? '') . ' ' . $desc . ' ' . $label);
                $matchesTaxon = str_contains($allText, $speciesLower);
                $matchesGenus = $taxonName && str_contains(strtolower($taxonName), $genus);
                $matchesKeywords = false;
                foreach ($speciesKeywords as $kw) {
                    if (str_contains($allText, $kw)) {
                        $matchesKeywords = true;
                        break;
                    }
                }
                if (! $matchesTaxon && ! $matchesGenus && ! $matchesKeywords) {
                    continue;
                }
            }

            $image = $r['image']['value'] ?? null;
            $imageUrl = $image ? $image . '?width=200' : null;
            if ($imageUrl && ! str_contains($imageUrl, 'Special:FilePath')) {
                $filename = basename(parse_url($image, PHP_URL_PATH));
                $imageUrl = 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($filename) . '?width=200';
            }

            $cultivarName = null;
            $varietyName = null;
            if ($taxonName && preg_match("/['\x{2018}\x{2019}](.+?)['\x{2018}\x{2019}]/u", $taxonName, $m)) {
                $cultivarName = $m[1];
            } elseif ($taxonName && preg_match('/var\.\s*(\S+)/i', $taxonName, $m)) {
                $varietyName = $m[1];
            }

            $date = $r['date']['value'] ?? null;
            if ($date) {
                $date = preg_replace('/^\+/', '', explode('T', $date)[0]);
            }

            $results[] = [
                'wikidata_id' => $wikidataId,
                'label'       => $label,
                'description' => $r['desc']['value'] ?? $r['originLabel']['value'] ?? '',
                'taxon_name'  => $taxonName,
                'cultivar'    => $cultivarName ?? $label,
                'variety'     => $varietyName,
                'image_url'   => $imageUrl,
                'origin'      => $r['originLabel']['value'] ?? null,
                'date'        => $date,
            ];
        }

        return $results;
    }

    /**
     * Get common keywords for a species to help filter cultivar results.
     */
    private function getSpeciesKeywords(string $species): array
    {
        $map = [
            'malus domestica' => ['pomme', 'apple', 'pommier'],
            'malus'           => ['pomme', 'apple', 'pommier'],
            'prunus avium'    => ['cerise', 'cherry', 'cerisier'],
            'prunus domestica' => ['prune', 'plum', 'prunier'],
            'prunus persica'  => ['pêche', 'peach', 'pêcher'],
            'prunus cerasus'  => ['cerise', 'cherry', 'griotte'],
            'pyrus communis'  => ['poire', 'pear', 'poirier'],
            'vitis vinifera'  => ['raisin', 'grape', 'vigne'],
            'citrus sinensis' => ['orange', 'oranger'],
            'citrus limon'    => ['citron', 'lemon', 'citronnier'],
            'fragaria'        => ['fraise', 'strawberry', 'fraisier'],
            'rosa'            => ['rose', 'rosier'],
            'solanum lycopersicum' => ['tomate', 'tomato'],
        ];

        $speciesLower = strtolower($species);
        foreach ($map as $key => $keywords) {
            if (str_contains($speciesLower, $key)) {
                return $keywords;
            }
        }

        return [];
    }

    /**
     * Entity API fallback — works when user types exact cultivar name.
     */
    private function searchCultivarsEntityApi(string $query, ?string $species, string $ua): array
    {
        $searchResponse = \Illuminate\Support\Facades\Http::timeout(10)
            ->withUserAgent($ua)
            ->get('https://www.wikidata.org/w/api.php', [
                'action'   => 'wbsearchentities',
                'search'   => $query,
                'language' => 'en',
                'format'   => 'json',
                'limit'    => 15,
            ]);

        $candidates = collect($searchResponse->json('search', []))
            ->filter(fn ($r) => str_contains(strtolower($r['description'] ?? ''), 'cultivar')
                || str_contains(strtolower($r['description'] ?? ''), 'variety')
                || str_contains(strtolower($r['description'] ?? ''), 'variété')
            );

        if ($candidates->isEmpty()) {
            return [];
        }

        $ids = $candidates->pluck('id')->implode('|');
        $detailResponse = \Illuminate\Support\Facades\Http::timeout(10)
            ->withUserAgent($ua)
            ->get('https://www.wikidata.org/w/api.php', [
                'action'    => 'wbgetentities',
                'ids'       => $ids,
                'languages' => 'fr|en',
                'format'    => 'json',
                'props'     => 'labels|descriptions|claims',
            ]);

        $results = [];
        foreach ($detailResponse->json('entities', []) as $id => $entity) {
            $claims = $entity['claims'] ?? [];
            $taxonName = $claims['P225'][0]['mainsnak']['datavalue']['value'] ?? null;

            $image = $claims['P18'][0]['mainsnak']['datavalue']['value'] ?? null;
            $imageUrl = $image
                ? 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode(str_replace(' ', '_', $image)) . '?width=200'
                : null;

            $date = $claims['P575'][0]['mainsnak']['datavalue']['value']['time']
                ?? $claims['P580'][0]['mainsnak']['datavalue']['value']['time']
                ?? null;
            if ($date) {
                $date = preg_replace('/^\+/', '', explode('T', $date)[0]);
            }

            $cultivarName = null;
            $varietyName = null;
            if ($taxonName && preg_match("/['\x{2018}\x{2019}](.+?)['\x{2018}\x{2019}]/u", $taxonName, $m)) {
                $cultivarName = $m[1];
            } elseif ($taxonName && preg_match('/var\.\s*(\S+)/i', $taxonName, $m)) {
                $varietyName = $m[1];
            }

            $label = $entity['labels']['fr']['value'] ?? $entity['labels']['en']['value'] ?? $id;
            $desc = $entity['descriptions']['fr']['value'] ?? $entity['descriptions']['en']['value'] ?? '';

            if ($species && $taxonName && ! str_contains(strtolower($taxonName), strtolower($species))) {
                continue;
            }

            $results[] = [
                'wikidata_id' => $id,
                'label'       => $label,
                'description' => $desc,
                'taxon_name'  => $taxonName,
                'cultivar'    => $cultivarName ?? $label,
                'variety'     => $varietyName,
                'image_url'   => $imageUrl,
                'origin'      => $claims['P495'][0]['mainsnak']['datavalue']['value']['id'] ?? null,
                'date'        => $date,
            ];
        }

        return $results;
    }

    /**
     * Get full cultivar details from Wikidata by ID.
     */
    public function cultivarDetails(Request $request): JsonResponse
    {
        $request->validate([
            'wikidata_id' => ['required', 'string', 'regex:/^Q\d+$/'],
        ]);

        $wikidataId = $request->input('wikidata_id');
        $ua = 'PhenoLab/1.0 (botanical garden app; contact@phenolab.org)';

        try {
            $sparql = <<<SPARQL
SELECT ?prop ?propLabel ?value ?valueLabel WHERE {
  wd:{$wikidataId} ?p ?value.
  ?prop wikibase:directClaim ?p.
  SERVICE wikibase:label { bd:serviceParam wikibase:language 'fr,en'. }
}
LIMIT 200
SPARQL;

            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->withUserAgent($ua)
                ->get('https://query.wikidata.org/sparql', [
                    'format' => 'json',
                    'query'  => $sparql,
                ]);

            $raw = $response->json('results.bindings', []);

            // Organize by property
            $props = [];
            foreach ($raw as $r) {
                $propLabel = $r['propLabel']['value'] ?? '';
                $valueLabel = $r['valueLabel']['value'] ?? '';
                $propId = basename($r['prop']['value'] ?? '');
                if (! isset($props[$propId])) {
                    $props[$propId] = ['label' => $propLabel, 'values' => []];
                }
                $props[$propId]['values'][] = $valueLabel;
            }

            // Extract structured info for PhenoLab
            $get = fn (string $pid) => $props[$pid]['values'] ?? [];
            $getFirst = fn (string $pid) => ($props[$pid]['values'] ?? [null])[0];

            $image = $getFirst('P18');
            $imageUrl = null;
            if ($image) {
                // Image value from SPARQL is a full URL like http://commons.wikimedia.org/wiki/Special:FilePath/Golden%20Delicious%20apples.jpg
                if (str_starts_with($image, 'http')) {
                    $imageUrl = $image . (str_contains($image, '?') ? '&' : '?') . 'width=400';
                } else {
                    $imageUrl = 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode(str_replace(' ', '_', $image)) . '?width=400';
                }
            }

            // Date formatting
            $date = $getFirst('P575') ?? $getFirst('P580');
            if ($date && preg_match('/(\d{4})/', $date, $m)) {
                $date = $m[1];
            }

            $info = [
                'wikidata_id'     => $wikidataId,
                'taxon_name'      => $getFirst('P225'),
                'image_url'       => $imageUrl,
                'origin'          => $getFirst('P495'),
                'date_discovered' => $date,
                'parents'         => $get('P1531') ?: $get('P3373'),   // P1531 = parent hybrides, P3373 alternative
                'children'        => $get('P40'),
                'characteristics' => $get('P1552'),
                'fruit_colors'    => $get('P462') ?: $get('P4743'),
                'mass_grams'      => $getFirst('P2067'),
                'usage'           => $get('P366'),
                'plu_codes'       => $get('P4288'),
                'commons_category' => $getFirst('P373'),
            ];

            // Clean: remove nulls and empty arrays
            $info = array_filter($info, fn ($v) => $v !== null && $v !== [] && $v !== '');

            return response()->json($info);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur Wikidata: ' . $e->getMessage(),
            ], 500);
        }
    }
}
