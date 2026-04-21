<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cultivar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CultivarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Cultivar::with('taxon:id,binomial_name,common_name_fr,family');

        if ($search = $request->query('search')) {
            $query->search($search);
        }

        if ($taxonId = $request->query('taxon_id')) {
            $query->forTaxon((int) $taxonId);
        }

        if ($upovCode = $request->query('upov_code')) {
            $query->forUpovCode($upovCode);
        }

        if ($request->query('registered_only')) {
            $query->registered();
        }

        $sortBy = $request->query('sort_by', 'name');
        $sortDir = $request->query('sort_dir', 'asc');
        $allowed = ['name', 'registration_date', 'origin_country', 'created_at'];
        if (in_array($sortBy, $allowed)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) ($request->query('per_page', 25)), 100);

        return response()->json($query->paginate($perPage));
    }

    public function show(Cultivar $cultivar): JsonResponse
    {
        $cultivar->load('taxon:id,binomial_name,common_name_fr,family,genus,species');

        return response()->json($cultivar);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'full_name'           => ['nullable', 'string', 'max:500'],
            'taxon_id'            => ['nullable', 'exists:taxons,id'],
            'upov_code'           => ['nullable', 'string', 'max:50'],
            'wikidata_id'         => ['nullable', 'string', 'max:20'],
            'type'                => ['nullable', 'string', 'max:50'],
            'synonyms'            => ['nullable', 'string', 'max:1000'],
            'origin_country'      => ['nullable', 'string', 'max:100'],
            'origin_region'       => ['nullable', 'string', 'max:100'],
            'breeder'             => ['nullable', 'string', 'max:255'],
            'year_introduced'     => ['nullable', 'string', 'max:20'],
            'parentage'           => ['nullable', 'string', 'max:500'],
            'fruit_color'         => ['nullable', 'string', 'max:100'],
            'fruit_size'          => ['nullable', 'string', 'max:50'],
            'fruit_shape'         => ['nullable', 'string', 'max:100'],
            'flesh_color'         => ['nullable', 'string', 'max:100'],
            'flesh_texture'       => ['nullable', 'string', 'max:100'],
            'flavor_profile'      => ['nullable', 'string', 'max:255'],
            'skin_type'           => ['nullable', 'string', 'max:100'],
            'harvest_period'      => ['nullable', 'string', 'max:100'],
            'flowering_period'    => ['nullable', 'string', 'max:100'],
            'maturity_group'      => ['nullable', 'string', 'max:50'],
            'storage_life'        => ['nullable', 'string', 'max:100'],
            'vigor'               => ['nullable', 'string', 'max:50'],
            'productivity'        => ['nullable', 'string', 'max:50'],
            'self_fertile'        => ['nullable', 'boolean'],
            'pollinators'         => ['nullable', 'string', 'max:500'],
            'rootstocks'          => ['nullable', 'string', 'max:500'],
            'disease_resistance'  => ['nullable', 'string', 'max:500'],
            'cold_hardiness'      => ['nullable', 'string', 'max:100'],
            'usage_types'         => ['nullable', 'string', 'max:255'],
            'image_url'           => ['nullable', 'string', 'max:500'],
            'description'         => ['nullable', 'string'],
            'notes'               => ['nullable', 'string'],
            'source'              => ['nullable', 'string', 'max:100'],
        ]);

        $cultivar = Cultivar::create($data);

        return response()->json($cultivar, 201);
    }

    public function update(Request $request, Cultivar $cultivar): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['sometimes', 'string', 'max:255'],
            'full_name'           => ['nullable', 'string', 'max:500'],
            'taxon_id'            => ['nullable', 'exists:taxons,id'],
            'upov_code'           => ['nullable', 'string', 'max:50'],
            'wikidata_id'         => ['nullable', 'string', 'max:20'],
            'type'                => ['nullable', 'string', 'max:50'],
            'synonyms'            => ['nullable', 'string', 'max:1000'],
            'origin_country'      => ['nullable', 'string', 'max:100'],
            'origin_region'       => ['nullable', 'string', 'max:100'],
            'breeder'             => ['nullable', 'string', 'max:255'],
            'year_introduced'     => ['nullable', 'string', 'max:20'],
            'parentage'           => ['nullable', 'string', 'max:500'],
            'fruit_color'         => ['nullable', 'string', 'max:100'],
            'fruit_size'          => ['nullable', 'string', 'max:50'],
            'fruit_shape'         => ['nullable', 'string', 'max:100'],
            'flesh_color'         => ['nullable', 'string', 'max:100'],
            'flesh_texture'       => ['nullable', 'string', 'max:100'],
            'flavor_profile'      => ['nullable', 'string', 'max:255'],
            'skin_type'           => ['nullable', 'string', 'max:100'],
            'harvest_period'      => ['nullable', 'string', 'max:100'],
            'flowering_period'    => ['nullable', 'string', 'max:100'],
            'maturity_group'      => ['nullable', 'string', 'max:50'],
            'storage_life'        => ['nullable', 'string', 'max:100'],
            'vigor'               => ['nullable', 'string', 'max:50'],
            'productivity'        => ['nullable', 'string', 'max:50'],
            'self_fertile'        => ['nullable', 'boolean'],
            'pollinators'         => ['nullable', 'string', 'max:500'],
            'rootstocks'          => ['nullable', 'string', 'max:500'],
            'disease_resistance'  => ['nullable', 'string', 'max:500'],
            'cold_hardiness'      => ['nullable', 'string', 'max:100'],
            'usage_types'         => ['nullable', 'string', 'max:255'],
            'image_url'           => ['nullable', 'string', 'max:500'],
            'description'         => ['nullable', 'string'],
            'notes'               => ['nullable', 'string'],
            'source'              => ['nullable', 'string', 'max:100'],
        ]);

        $cultivar->update($data);

        return response()->json($cultivar);
    }

    public function destroy(Cultivar $cultivar): JsonResponse
    {
        $cultivar->delete();

        return response()->json(['message' => 'Cultivar supprimé.']);
    }

    /**
     * Search cultivars — combines local DB results with Wikidata.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query'   => ['required', 'string', 'min:2', 'max:100'],
            'species' => ['nullable', 'string', 'max:100'],
        ]);

        $q = $request->input('query');
        $species = $request->input('species');

        // 1. Local DB search
        $localQuery = Cultivar::with('taxon:id,binomial_name,common_name_fr')
            ->search($q);

        if ($species) {
            $localQuery->whereHas('taxon', function ($tq) use ($species) {
                $tq->where('binomial_name', 'like', "%{$species}%");
            });
        }

        $localResults = $localQuery->limit(20)->get()->map(fn (Cultivar $c) => [
            'id'              => $c->id,
            'name'            => $c->name,
            'full_name'       => $c->full_name,
            'taxon_name'      => $c->taxon?->binomial_name,
            'common_name'     => $c->taxon?->common_name_fr,
            'synonyms'        => $c->synonyms,
            'origin_country'  => $c->origin_country,
            'breeder'         => $c->breeder,
            'source'          => $c->source ?? 'local',
            'wikidata_id'     => $c->wikidata_id,
            'cultivar_id'     => $c->id,
            'image_url'       => $c->image_url,
            'registration_status' => $c->registration_status,
        ]);

        return response()->json([
            'results' => $localResults->values(),
            'total'   => $localResults->count(),
        ]);
    }

    /**
     * Statistics about the cultivar database.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total'           => Cultivar::count(),
            'with_taxon'      => Cultivar::whereNotNull('taxon_id')->count(),
            'registered'      => Cultivar::where('registration_status', 'Registered')->count(),
            'sources'         => Cultivar::selectRaw('source, count(*) as count')
                                    ->groupBy('source')
                                    ->pluck('count', 'source'),
            'top_species'     => Cultivar::join('taxons', 'cultivars.taxon_id', '=', 'taxons.id')
                                    ->selectRaw('taxons.binomial_name, taxons.common_name_fr, count(*) as count')
                                    ->groupBy('taxons.binomial_name', 'taxons.common_name_fr')
                                    ->orderByDesc('count')
                                    ->limit(15)
                                    ->get(),
        ]);
    }
}
