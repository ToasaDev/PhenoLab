<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Models\Plant;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SitePlanLayer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SiteController extends Controller
{
    use Concerns\SanitizesOrdering;
    private function canManageSite(Site $site): bool
    {
        $user = Auth::user();
        if ($user === null) return false;
        if ($user->is_staff) return true;
        if ($site->owner_id === $user->id) return true;
        // Group members can manage shared sites
        if ($site->group_id && in_array($site->group_id, $user->groupIds())) return true;

        return false;
    }

    private function visibleSitesQuery(): Builder
    {
        $query = Site::query();
        $user = Auth::user();

        if ($user?->is_staff) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($user) {
            $visible->where('is_private', false);

            if ($user !== null) {
                $visible->orWhere('owner_id', $user->id);
                // Include sites shared with user's groups
                $groupIds = $user->groupIds();
                if (!empty($groupIds)) {
                    $visible->orWhereIn('group_id', $groupIds);
                }
            }
        });
    }

    /**
     * Paginated list of sites with filters and annotations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->visibleSitesQuery()
            ->with('owner:id,name', 'siteCategory:id,name,slug,parent_id,icon,color')
            ->withCount('plants')
            ->addSelect([
                'observations_count' => Observation::selectRaw('count(*)')
                    ->join('plants', 'observations.plant_id', '=', 'plants.id')
                    ->whereColumn('plants.site_id', 'sites.id'),
            ]);

        if ($env = $request->query('environment')) {
            $query->where('environment', $env);
        }

        if ($catId = $request->query('site_category_id')) {
            $ids = $this->categoryAndDescendantIds((int) $catId);
            $query->whereIn('site_category_id', $ids);
        }

        if ($request->has('is_private')) {
            $query->where('is_private', $request->boolean('is_private'));
        }

        if ($search = $request->query('search')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('soil_type', 'like', "%{$search}%")
                  ->orWhere('climate_zone', 'like', "%{$search}%");
            });
        }

        [$column, $direction] = $this->parseOrdering(
            $request->query('ordering', 'name'),
            ['name', 'created_at', 'environment', 'site_category_id', 'altitude', 'id'],
            'name'
        );
        $query->orderBy($column, $direction);

        $perPage = min((int) ($request->query('per_page') ?? $request->query('page_size') ?? 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Resolve a site category and all its descendants for hierarchical filtering.
     * Returns [$id] if not found (so the query yields no results).
     */
    private function categoryAndDescendantIds(int $categoryId): array
    {
        $category = SiteCategory::with('children.children.children')->find($categoryId);

        if (! $category) {
            return [$categoryId];
        }

        return $category->descendantIds();
    }

    /**
     * Show a single site with full details.
     */
    public function show(int $id): JsonResponse
    {
        $site = $this->visibleSitesQuery()
            ->with('owner:id,name', 'layers', 'siteCategory:id,name,slug,parent_id,icon,color')
            ->withCount('plants')
            ->addSelect([
                'observations_count' => Observation::selectRaw('count(*)')
                    ->join('plants', 'observations.plant_id', '=', 'plants.id')
                    ->whereColumn('plants.site_id', 'sites.id'),
            ])
            ->findOrFail($id);

        return response()->json($site);
    }

    /**
     * Create a new site.
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'latitude'          => ['required', 'numeric', 'between:-90,90'],
            'longitude'         => ['required', 'numeric', 'between:-180,180'],
            'altitude'          => ['nullable', 'numeric'],
            'environment'       => ['required', 'string', 'in:'.implode(',', array_keys(Site::ENVIRONMENT_TYPES))],
            'site_category_id'  => ['nullable', 'integer', 'exists:site_categories,id'],
            'is_private'        => ['nullable', 'boolean'],
            'soil_type'         => ['nullable', 'string', 'max:100'],
            'exposure'          => ['nullable', 'string', 'in:nord,nord-est,est,sud-est,sud,sud-ouest,ouest,nord-ouest'],
            'slope'             => ['nullable', 'string', 'in:flat,gentle,moderate,steep'],
            'climate_zone'      => ['nullable', 'string', 'max:50'],
            'plan_width_meters' => ['nullable', 'numeric'],
            'plan_height_meters'=> ['nullable', 'numeric'],
            'group_id'          => ['nullable', 'exists:user_groups,id'],
        ]);

        // Verify user belongs to the group if specified
        if (!empty($data['group_id'])) {
            $groupIds = Auth::user()->groupIds();
            if (!in_array((int) $data['group_id'], $groupIds) && !Auth::user()->is_staff) {
                return response()->json(['message' => 'Vous ne faites pas partie de ce groupe.'], 403);
            }
        }

        $data['owner_id'] = Auth::id();

        $site = Site::create($data);

        return response()->json($site->load('owner:id,name', 'siteCategory:id,name,slug,parent_id,icon,color'), 201);
    }

    /**
     * Update a site (owner or staff only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $site = Site::findOrFail($id);

        if (Auth::id() !== $site->owner_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'name'              => ['sometimes', 'required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'latitude'          => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude'         => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'altitude'          => ['nullable', 'numeric'],
            'environment'       => ['sometimes', 'required', 'string', 'in:'.implode(',', array_keys(Site::ENVIRONMENT_TYPES))],
            'site_category_id'  => ['nullable', 'integer', 'exists:site_categories,id'],
            'is_private'        => ['nullable', 'boolean'],
            'soil_type'         => ['nullable', 'string', 'max:100'],
            'exposure'          => ['nullable', 'string', 'in:nord,nord-est,est,sud-est,sud,sud-ouest,ouest,nord-ouest'],
            'slope'             => ['nullable', 'string', 'in:flat,gentle,moderate,steep'],
            'climate_zone'      => ['nullable', 'string', 'max:50'],
            'plan_width_meters' => ['nullable', 'numeric'],
            'plan_height_meters'=> ['nullable', 'numeric'],
        ]);

        $site->update($data);

        return response()->json($site->load('owner:id,name', 'siteCategory:id,name,slug,parent_id,icon,color'));
    }

    /**
     * Delete a site (owner or staff only).
     */
    public function destroy(int $id): JsonResponse
    {
        $site = Site::findOrFail($id);

        if (Auth::id() !== $site->owner_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $site->delete();

        return response()->json(null, 204);
    }

    /**
     * Return a GeoJSON FeatureCollection of all accessible sites.
     */
    public function geojson(): JsonResponse
    {
        $sites = $this->visibleSitesQuery()
            ->select('id', 'name', 'latitude', 'longitude', 'environment', 'site_category_id', 'altitude', 'is_private', 'owner_id')
            ->with('siteCategory:id,name,slug,parent_id,icon,color')
            ->withCount('plants')
            ->get();

        $features = $sites->map(function ($site) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type'        => 'Point',
                    'coordinates' => [(float) $site->longitude, (float) $site->latitude],
                ],
                'properties' => [
                    'id'                => $site->id,
                    'name'              => $site->name,
                    'environment'       => $site->environment,
                    'site_category_id'  => $site->site_category_id,
                    'site_category'     => $site->siteCategory ? [
                        'id'   => $site->siteCategory->id,
                        'name' => $site->siteCategory->name,
                        'slug' => $site->siteCategory->slug,
                    ] : null,
                    'altitude'          => $site->altitude,
                    'is_private'        => $site->is_private,
                    'plants_count'      => $site->plants_count,
                ],
            ];
        });

        return response()->json([
            'type'     => 'FeatureCollection',
            'features' => $features->values(),
        ]);
    }

    /**
     * Find sites within a radius using the Haversine formula.
     */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'lat'       => ['required', 'numeric', 'between:-90,90'],
            'lon'       => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lat = $request->query('lat');
        $lon = $request->query('lon');
        $radius = $request->query('radius_km', 10);

        $sites = $this->visibleSitesQuery()
            ->nearby($lat, $lon, $radius)
            ->withCount('plants')
            ->get();

        return response()->json($sites);
    }

    /**
     * Return sites owned by the current user.
     */
    public function mySites(): JsonResponse
    {
        $userId = Auth::id();
        $groupIds = Auth::user()->groupIds();

        $sites = Site::where(function ($q) use ($userId, $groupIds) {
                $q->where('owner_id', $userId);
                if (!empty($groupIds)) {
                    $q->orWhereIn('group_id', $groupIds);
                }
            })
            ->withCount('plants')
            ->orderBy('name')
            ->get();

        return response()->json($sites);
    }

    /**
     * Paginated, filtered, sorted plants for a specific site.
     */
    public function plants(int $id, Request $request): JsonResponse
    {
        $site = $this->visibleSitesQuery()->findOrFail($id);
        $user = Auth::user();

        $query = Plant::where('site_id', $site->id)
            ->with('taxon:id,binomial_name,common_name_fr,genus,species,family', 'category:id,name,icon,category_type', 'position:id,label,site_id', 'mainPhoto:id,plant_id,image,is_main_photo')
            ->withCount('observations', 'photos')
            ->addSelect([
                'last_observation_date' => Observation::select('observation_date')
                    ->whereColumn('plant_id', 'plants.id')
                    ->orderByDesc('observation_date')
                    ->limit(1),
            ]);

        if (! $user?->is_staff) {
            $query->where(function ($visible) use ($user) {
                $visible->where('is_private', false);

                if ($user !== null) {
                    $visible->orWhere('owner_id', $user->id);
                }
            });
        }

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

        if ($category = $request->query('category')) {
            $query->where('category_id', $category);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($health = $request->query('health_status')) {
            $query->where('health_status', $health);
        }

        $sortBy = $request->query('sort_by', 'name');
        $sortDir = $request->query('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($request->query('per_page') ?? $request->query('page_size') ?? 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Site statistics (plants by status/health/category, observations count, photos count).
     */
    public function statistics(int $id): JsonResponse
    {
        $site = $this->visibleSitesQuery()->findOrFail($id);
        $user = Auth::user();

        $visiblePlants = Plant::where('site_id', $id);

        if (! $user?->is_staff) {
            $visiblePlants->where(function ($visible) use ($user) {
                $visible->where('is_private', false);

                if ($user !== null) {
                    $visible->orWhere('owner_id', $user->id);
                }
            });
        }

        $visibleObservations = Observation::whereHas('plant', function ($query) use ($id, $user) {
            $query->where('site_id', $id);

            if (! $user?->is_staff) {
                $query->where(function ($visible) use ($user) {
                    $visible->where('is_private', false);

                    if ($user !== null) {
                        $visible->orWhere('owner_id', $user->id);
                    }
                });
            }
        });

        if (! $user?->is_staff) {
            $visibleObservations->where(function ($visible) use ($user) {
                $visible->where('is_public', true);

                if ($user !== null) {
                    $visible->orWhere('observer_id', $user->id);
                }
            });
        }

        $plantsByStatus = (clone $visiblePlants)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $plantsByHealth = (clone $visiblePlants)
            ->selectRaw('health_status, count(*) as count')
            ->groupBy('health_status')
            ->pluck('count', 'health_status');

        $plantsByCategory = (clone $visiblePlants)
            ->join('categories', 'plants.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, count(*) as count')
            ->groupBy('categories.name')
            ->pluck('count', 'category_name');

        $observationsCount = (clone $visibleObservations)->count();
        $photosCount = DB::table('plant_photos')
            ->join('plants', 'plant_photos.plant_id', '=', 'plants.id')
            ->where('plants.site_id', $id)
            ->when(! $user?->is_staff, function ($query) use ($user) {
                $query->where(function ($visible) use ($user) {
                    $visible->where('plant_photos.is_public', true)
                        ->where('plants.is_private', false);

                    if ($user !== null) {
                        $visible->orWhere('plant_photos.photographer_id', $user->id)
                            ->orWhere('plants.owner_id', $user->id);
                    }
                });
            })
            ->count();

        return response()->json([
            'plants_count'       => (clone $visiblePlants)->count(),
            'plants_by_status'   => $plantsByStatus,
            'plants_by_health'   => $plantsByHealth,
            'plants_by_category' => $plantsByCategory,
            'observations_count' => $observationsCount,
            'photos_count'       => $photosCount,
        ]);
    }

    /**
     * PATCH the drawing overlay JSON for a site.
     */
    public function updateDrawingOverlay(Request $request, int $id): JsonResponse
    {
        $site = Site::findOrFail($id);

        if (Auth::id() !== $site->owner_id && ! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $data = $request->validate([
            'drawing_overlay' => ['nullable', 'array'],
        ]);

        $site->update(['drawing_overlay' => $data['drawing_overlay']]);

        return response()->json($site);
    }

    /**
     * List layers for a site.
     */
    public function listLayers(int $id): JsonResponse
    {
        $site = $this->visibleSitesQuery()->findOrFail($id);

        return response()->json($site->layers()->orderByDesc('start_date')->get());
    }

    /**
     * Create a new layer for a site.
     */
    public function createLayer(Request $request, int $id): JsonResponse
    {
        $site = Site::findOrFail($id);

        if (! $this->canManageSite($site)) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['nullable', 'date', 'after:start_date'],
            'is_active'        => ['nullable', 'boolean'],
            'drawing_overlay'  => ['nullable', 'array'],
            'notes'            => ['nullable', 'string'],
            'copy_from_active' => ['nullable', 'boolean'],
            'source_layer_id'  => ['nullable', 'integer', 'exists:site_plan_layers,id'],
        ]);

        $copyFromActive = (bool) ($data['copy_from_active'] ?? false);
        $sourceLayerId  = $data['source_layer_id'] ?? null;
        unset($data['copy_from_active'], $data['source_layer_id']);

        $data['site_id'] = $site->id;

        // When copying, prefer the active layer; fall back to the most
        // recently created one if none is currently marked active (which
        // can happen after manual edits or imports).
        $sourceLayer = null;
        if ($copyFromActive) {
            if ($sourceLayerId) {
                $sourceLayer = SitePlanLayer::where('site_id', $site->id)
                    ->where('id', $sourceLayerId)
                    ->first();
            }
            $sourceLayer = $sourceLayer
                ?? SitePlanLayer::where('site_id', $site->id)
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first()
                ?? SitePlanLayer::where('site_id', $site->id)
                    ->orderByDesc('id')
                    ->first();
        }

        // If copying and no drawing_overlay was explicitly provided, clone it.
        if ($sourceLayer && ! array_key_exists('drawing_overlay', $data)) {
            $data['drawing_overlay'] = $sourceLayer->drawing_overlay;
        }

        $layer = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $sourceLayer) {
            $layer = SitePlanLayer::create($data);

            if ($sourceLayer) {
                $sourcePositions = \App\Models\PlantLayerPosition::where('layer_id', $sourceLayer->id)->get();
                $now = now();
                $rows = $sourcePositions->map(fn ($p) => [
                    'layer_id'       => $layer->id,
                    'plant_id'       => $p->plant_id,
                    'map_position_x' => $p->map_position_x,
                    'map_position_y' => $p->map_position_y,
                    'notes'          => $p->notes,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ])->all();

                if (! empty($rows)) {
                    \App\Models\PlantLayerPosition::insert($rows);
                }
            }

            // If the new layer is active, deactivate the others on the same site.
            if ($layer->is_active) {
                SitePlanLayer::where('site_id', $layer->site_id)
                    ->where('id', '!=', $layer->id)
                    ->update(['is_active' => false]);
            }

            return $layer;
        });

        return response()->json($layer, 201);
    }

    /**
     * Update a layer.
     */
    public function updateLayer(Request $request, int $id, int $layerId): JsonResponse
    {
        $site = Site::findOrFail($id);

        if (! $this->canManageSite($site)) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $layer = SitePlanLayer::where('site_id', $id)->findOrFail($layerId);

        $data = $request->validate([
            'name'            => ['sometimes', 'required', 'string', 'max:100'],
            'start_date'      => ['sometimes', 'required', 'date'],
            'end_date'        => ['nullable', 'date', 'after:start_date'],
            'is_active'       => ['nullable', 'boolean'],
            'drawing_overlay' => ['nullable', 'array'],
            'notes'           => ['nullable', 'string'],
        ]);

        $layer->update($data);

        return response()->json($layer);
    }

    /**
     * Delete a layer.
     */
    public function deleteLayer(int $id, int $layerId): JsonResponse
    {
        $site = Site::findOrFail($id);

        if (! $this->canManageSite($site)) {
            return response()->json(['detail' => 'Non autorise.'], 403);
        }

        $layer = SitePlanLayer::where('site_id', $id)->findOrFail($layerId);

        $layer->delete();

        return response()->json(null, 204);
    }
}
