<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Observation;
use App\Models\Plant;
use App\Models\Site;
use App\Models\Taxon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GlobalSearchController extends Controller
{
    /**
     * Search across plants, sites, observations, and taxons.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q'                  => ['nullable', 'string', 'max:255'],
            'type'               => ['nullable', 'string'],
            'cult_exposure'      => ['nullable', 'string', 'max:30'],
            'cult_difficulty'    => ['nullable', 'string', 'max:30'],
            'cult_watering'      => ['nullable', 'string', 'max:30'],
            'cult_soil_type'     => ['nullable', 'string', 'max:30'],
            'cult_soil_drainage' => ['nullable', 'string', 'max:30'],
            'cult_usage_type'    => ['nullable', 'string', 'max:30'],
            'cult_usda_zone'     => ['nullable', 'string', 'max:20'],
            'cult_is_edible'     => ['nullable', 'boolean'],
            'cult_is_toxic'      => ['nullable', 'boolean'],
        ]);

        $rawQ = trim((string) $request->query('q', ''));
        $q = $this->escapeLike($rawQ);
        $type = $request->query('type', 'all');
        $limit = min((int) $request->query('limit', 10), 50);
        $mine = $request->boolean('mine');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // Cultivation filter set (only applied to plants)
        $cultFilters = array_filter([
            'exposure'              => $request->query('cult_exposure'),
            'cultivation_difficulty'=> $request->query('cult_difficulty'),
            'watering_needs'        => $request->query('cult_watering'),
            'soil_drainage'         => $request->query('cult_soil_drainage'),
            'usda_zone'             => $request->query('cult_usda_zone'),
        ], fn ($v) => $v !== null && $v !== '');
        $cultJsonAny = array_filter([
            'soil_types'  => $request->query('cult_soil_type'),
            'usage_types' => $request->query('cult_usage_type'),
        ], fn ($v) => $v !== null && $v !== '');
        $cultBool = [];
        if ($request->filled('cult_is_edible')) $cultBool['is_edible'] = $request->boolean('cult_is_edible');
        if ($request->filled('cult_is_toxic'))  $cultBool['is_toxic']  = $request->boolean('cult_is_toxic');

        $hasCultivationFilter = !empty($cultFilters) || !empty($cultJsonAny) || !empty($cultBool);

        // Require either a query or at least one cultivation filter
        if ($rawQ === '' && !$hasCultivationFilter) {
            return response()->json([
                'message' => 'Fournir un terme de recherche ou au moins un filtre de culture.',
            ], 422);
        }

        $results = [];

        // --- Plants ---
        if (in_array($type, ['all', 'plants'])) {
            $plantQuery = Plant::query()
                ->with('taxon:id,binomial_name,common_name_fr', 'site:id,name', 'cultivationProfile')
                ->select('id', 'name', 'taxon_id', 'site_id', 'status', 'owner_id', 'is_private');

            if ($rawQ !== '') {
                $plantQuery->where(function ($pq) use ($q) {
                    $pq->where('name', 'like', "%{$q}%")
                       ->orWhere('description', 'like', "%{$q}%")
                       ->orWhereHas('taxon', function ($tq) use ($q) {
                           $tq->where('binomial_name', 'like', "%{$q}%")
                              ->orWhere('common_name_fr', 'like', "%{$q}%");
                       });
                });
            }

            // Privacy: exclude private plants unless owner
            $plantQuery->where(function ($pq) {
                $pq->where('is_private', false);
                if (Auth::check()) {
                    $pq->orWhere('owner_id', Auth::id());
                }
            });

            if ($mine && Auth::check()) {
                $plantQuery->where('owner_id', Auth::id());
            }

            if ($dateFrom) $plantQuery->where('created_at', '>=', $dateFrom);
            if ($dateTo)   $plantQuery->where('created_at', '<=', $dateTo);

            // Cultivation profile filters
            if ($hasCultivationFilter) {
                $plantQuery->whereHas('cultivationProfile', function ($cp) use ($cultFilters, $cultJsonAny, $cultBool) {
                    foreach ($cultFilters as $col => $val) {
                        $cp->where($col, $val);
                    }
                    foreach ($cultJsonAny as $col => $val) {
                        // SQLite-friendly JSON contains check
                        $cp->where($col, 'like', '%"'.$val.'"%');
                    }
                    foreach ($cultBool as $col => $val) {
                        $cp->where($col, $val);
                    }
                });
            }

            $results['plants'] = $plantQuery->limit($limit)->get()->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'type'          => 'plant',
                'binomial_name' => $p->taxon->binomial_name ?? null,
                'common_name'   => $p->taxon->common_name_fr ?? null,
                'site_name'     => $p->site->name ?? null,
                'status'        => $p->status,
                'cultivation'   => $p->cultivationProfile ? [
                    'exposure'   => $p->cultivationProfile->exposure,
                    'watering'   => $p->cultivationProfile->watering_needs,
                    'difficulty' => $p->cultivationProfile->cultivation_difficulty,
                    'is_edible'  => $p->cultivationProfile->is_edible,
                    'is_toxic'   => $p->cultivationProfile->is_toxic,
                ] : null,
            ]);
        }

        // --- Sites --- (text search only)
        if ($rawQ !== '' && in_array($type, ['all', 'sites'])) {
            $siteQuery = Site::where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'environment', 'owner_id', 'is_private');

            // Privacy: exclude private sites unless owner
            $siteQuery->where(function ($sq) {
                $sq->where('is_private', false);
                if (Auth::check()) {
                    $sq->orWhere('owner_id', Auth::id());
                }
            });

            if ($mine && Auth::check()) {
                $siteQuery->where('owner_id', Auth::id());
            }

            $results['sites'] = $siteQuery->limit($limit)->get()->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'type'        => 'site',
                'environment' => $s->environment,
            ]);
        }

        // --- Observations --- (text search only)
        if ($rawQ !== '' && in_array($type, ['all', 'observations'])) {
            $obsQuery = Observation::where(function ($oq) use ($q) {
                $oq->where('notes', 'like', "%{$q}%")
                   ->orWhereHas('plant', function ($pq) use ($q) {
                       $pq->where('name', 'like', "%{$q}%");
                   });
            })
            ->with('plant:id,name', 'phenologicalStage:id,stage_code,stage_description')
            ->select('id', 'observation_date', 'plant_id', 'phenological_stage_id', 'observer_id', 'is_public');

            // Privacy: exclude non-public observations unless observer
            $obsQuery->where(function ($oq) {
                $oq->where('is_public', true);
                if (Auth::check()) {
                    $oq->orWhere('observer_id', Auth::id());
                }
            });

            if ($mine && Auth::check()) {
                $obsQuery->where('observer_id', Auth::id());
            }

            if ($dateFrom) $obsQuery->where('observation_date', '>=', $dateFrom);
            if ($dateTo)   $obsQuery->where('observation_date', '<=', $dateTo);

            $results['observations'] = $obsQuery->limit($limit)->get()->map(fn ($o) => [
                'id'               => $o->id,
                'type'             => 'observation',
                'observation_date' => $o->observation_date,
                'plant_name'       => $o->plant->name ?? null,
                'stage_code'       => $o->phenologicalStage->stage_code ?? null,
                'stage_description'=> $o->phenologicalStage->stage_description ?? null,
            ]);
        }

        // --- Taxons --- (text search only)
        if ($rawQ !== '' && in_array($type, ['all', 'taxons'])) {
            $taxonQuery = Taxon::where(function ($tq) use ($q) {
                $tq->where('binomial_name', 'like', "%{$q}%")
                   ->orWhere('common_name_fr', 'like', "%{$q}%")
                   ->orWhere('common_name_en', 'like', "%{$q}%")
                   ->orWhere('common_name_it', 'like', "%{$q}%")
                   ->orWhere('genus', 'like', "%{$q}%")
                   ->orWhere('family', 'like', "%{$q}%");
            })
            ->select('id', 'binomial_name', 'common_name_fr', 'family', 'genus', 'species');

            $results['taxons'] = $taxonQuery->limit($limit)->get()->map(fn ($t) => [
                'id'            => $t->id,
                'type'          => 'taxon',
                'binomial_name' => $t->binomial_name,
                'common_name'   => $t->common_name_fr,
                'family'        => $t->family,
            ]);
        }

        return response()->json($results);
    }
}
