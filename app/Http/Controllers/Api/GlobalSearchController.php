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
            'q' => ['required', 'string', 'min:1'],
        ]);

        $q = $this->escapeLike($request->query('q'));
        $type = $request->query('type', 'all');
        $limit = min((int) $request->query('limit', 10), 50);
        $mine = $request->boolean('mine');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $results = [];

        // --- Plants ---
        if (in_array($type, ['all', 'plants'])) {
            $plantQuery = Plant::where(function ($pq) use ($q) {
                $pq->where('name', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%")
                   ->orWhereHas('taxon', function ($tq) use ($q) {
                       $tq->where('binomial_name', 'like', "%{$q}%")
                          ->orWhere('common_name_fr', 'like', "%{$q}%");
                   });
            })
            ->with('taxon:id,binomial_name,common_name_fr', 'site:id,name')
            ->select('id', 'name', 'taxon_id', 'site_id', 'status', 'owner_id', 'is_private');

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

            $results['plants'] = $plantQuery->limit($limit)->get()->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'type'          => 'plant',
                'binomial_name' => $p->taxon->binomial_name ?? null,
                'common_name'   => $p->taxon->common_name_fr ?? null,
                'site_name'     => $p->site->name ?? null,
                'status'        => $p->status,
            ]);
        }

        // --- Sites ---
        if (in_array($type, ['all', 'sites'])) {
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

        // --- Observations ---
        if (in_array($type, ['all', 'observations'])) {
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

        // --- Taxons ---
        if (in_array($type, ['all', 'taxons'])) {
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
