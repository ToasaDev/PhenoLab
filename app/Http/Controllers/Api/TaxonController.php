<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Taxon;
use App\Services\GbifImportService;
use App\Services\GbifService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaxonController extends Controller
{
    /**
     * Paginated list of taxons with filters and annotations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Taxon::withCount('plants');

        if ($kingdom = $request->query('kingdom')) {
            $query->where('kingdom', $kingdom);
        }

        if ($phylum = $request->query('phylum')) {
            $query->where('phylum', $phylum);
        }

        if ($className = $request->query('class_name')) {
            $query->where('class_name', $className);
        }

        if ($order = $request->query('order')) {
            $query->where('order', $order);
        }

        if ($family = $request->query('family')) {
            $query->where('family', $family);
        }

        if ($genus = $request->query('genus')) {
            $query->where('genus', $genus);
        }

        if ($search = $request->query('search')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('binomial_name', 'like', "%{$search}%")
                  ->orWhere('genus', 'like', "%{$search}%")
                  ->orWhere('species', 'like', "%{$search}%")
                  ->orWhere('family', 'like', "%{$search}%")
                  ->orWhere('common_name_fr', 'like', "%{$search}%")
                  ->orWhere('common_name_it', 'like', "%{$search}%")
                  ->orWhere('common_name_en', 'like', "%{$search}%")
                  ->orWhereHas('alternativeNames', function ($nq) use ($search) {
                      $nq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $query->orderBy('binomial_name');

        $perPage = min((int) $request->query('per_page', 20), 100);

        $results = $query->paginate($perPage);

        // Add display_name to each taxon
        $results->getCollection()->transform(function ($taxon) {
            $taxon->display_name = $taxon->common_name_fr ?: $taxon->binomial_name;
            return $taxon;
        });

        return response()->json($results);
    }

    /**
     * Show a taxon with its alternative names.
     */
    public function show(int $id): JsonResponse
    {
        $taxon = Taxon::with('alternativeNames')
            ->withCount('plants')
            ->findOrFail($id);

        $taxon->display_name = $taxon->common_name_fr ?: $taxon->binomial_name;

        return response()->json($taxon);
    }

    /**
     * Create a new taxon.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'taxon_id'            => ['required', 'string', 'max:20', 'unique:taxons,taxon_id'],
            'kingdom'             => ['nullable', 'string', 'max:100'],
            'phylum'              => ['nullable', 'string', 'max:100'],
            'class_name'          => ['nullable', 'string', 'max:100'],
            'order'               => ['nullable', 'string', 'max:100'],
            'family'              => ['nullable', 'string', 'max:100'],
            'genus'               => ['required', 'string', 'max:100'],
            'species'             => ['required', 'string', 'max:100'],
            'binomial_name'       => ['nullable', 'string', 'max:255'],
            'subspecies'          => ['nullable', 'string', 'max:100'],
            'variety'             => ['nullable', 'string', 'max:100'],
            'cultivar'            => ['nullable', 'string', 'max:100'],
            'common_name_fr'      => ['nullable', 'string', 'max:1000'],
            'common_name_it'      => ['nullable', 'string', 'max:1000'],
            'common_name_en'      => ['nullable', 'string', 'max:1000'],
            'author'              => ['nullable', 'string', 'max:1000'],
            'publication_year'    => ['nullable', 'integer'],
            'gbif_id'             => ['nullable', 'integer', 'unique:taxons,gbif_id'],
            'gbif_status'         => ['nullable', 'string', 'max:50'],
            'gbif_rank'           => ['nullable', 'string', 'max:50'],
            'gbif_canonical_name' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['kingdom'] = $data['kingdom'] ?? 'Plantae';

        // Auto-generate binomial_name if not provided
        if (empty($data['binomial_name']) && ! empty($data['genus']) && ! empty($data['species'])) {
            $data['binomial_name'] = $data['genus'] . ' ' . $data['species'];
        }

        $taxon = Taxon::create($data);

        return response()->json($taxon, 201);
    }

    /**
     * Update a taxon.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $taxon = Taxon::findOrFail($id);

        $data = $request->validate([
            'taxon_id'            => ['sometimes', 'required', 'string', 'max:20', "unique:taxons,taxon_id,{$id}"],
            'kingdom'             => ['nullable', 'string', 'max:100'],
            'phylum'              => ['nullable', 'string', 'max:100'],
            'class_name'          => ['nullable', 'string', 'max:100'],
            'order'               => ['nullable', 'string', 'max:100'],
            'family'              => ['nullable', 'string', 'max:100'],
            'genus'               => ['sometimes', 'required', 'string', 'max:100'],
            'species'             => ['sometimes', 'required', 'string', 'max:100'],
            'binomial_name'       => ['nullable', 'string', 'max:255'],
            'subspecies'          => ['nullable', 'string', 'max:100'],
            'variety'             => ['nullable', 'string', 'max:100'],
            'cultivar'            => ['nullable', 'string', 'max:100'],
            'common_name_fr'      => ['nullable', 'string', 'max:1000'],
            'common_name_it'      => ['nullable', 'string', 'max:1000'],
            'common_name_en'      => ['nullable', 'string', 'max:1000'],
            'author'              => ['nullable', 'string', 'max:1000'],
            'publication_year'    => ['nullable', 'integer'],
            'gbif_id'             => ['nullable', 'integer', "unique:taxons,gbif_id,{$id}"],
            'gbif_status'         => ['nullable', 'string', 'max:50'],
            'gbif_rank'           => ['nullable', 'string', 'max:50'],
            'gbif_canonical_name' => ['nullable', 'string', 'max:1000'],
        ]);

        // Auto-generate binomial_name if genus and species are updated
        $genus = $data['genus'] ?? $taxon->genus;
        $species = $data['species'] ?? $taxon->species;
        if ($genus && $species) {
            $data['binomial_name'] = $genus . ' ' . $species;
        }

        $taxon->update($data);

        return response()->json($taxon);
    }

    /**
     * Delete a taxon.
     */
    public function destroy(int $id): JsonResponse
    {
        $taxon = Taxon::findOrFail($id);
        $taxon->delete();

        return response()->json(null, 204);
    }

    // ── GBIF Sync Endpoints (ported from Django TaxonAdmin) ────────────

    /**
     * Sync a taxon from GBIF by scientific name (backbone match).
     *
     * POST /api/v1/taxons/sync-gbif
     */
    public function syncGbif(Request $request, GbifImportService $importService, GbifService $gbifService): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        $data = $request->validate([
            'sync_mode'        => ['required', 'in:backbone_match,search'],
            'search_query'     => ['required', 'string', 'min:2'],
            'import_limit'     => ['nullable', 'integer', 'min:1', 'max:500'],
            'strict_mode'      => ['nullable', 'boolean'],
            'fetch_vernacular' => ['nullable', 'boolean'],
        ]);

        $mode = $data['sync_mode'];
        $query = $data['search_query'];
        $limit = $data['import_limit'] ?? 20;
        $strict = $data['strict_mode'] ?? false;
        $fetchVernacular = $data['fetch_vernacular'] ?? true;

        $results = ['synced' => [], 'errors' => []];

        if ($mode === 'backbone_match') {
            $syncResult = $importService->syncByScientificName($query, $strict, $fetchVernacular);

            if ($syncResult['success']) {
                $results['synced'][] = [
                    'taxon_id' => $syncResult['taxon']->id,
                    'name'     => $syncResult['taxon']->binomial_name,
                    'created'  => $syncResult['created'],
                    'message'  => $syncResult['message'],
                ];
            } else {
                $results['errors'][] = $syncResult['message'];
            }
        } else {
            // search mode: search GBIF and import matching species
            $searchResults = $gbifService->searchTaxa($query, $limit);

            foreach ($searchResults['results'] ?? [] as $item) {
                $rank = strtoupper($item['rank'] ?? '');
                if (! in_array($rank, GbifImportService::ALLOWED_RANKS)) {
                    continue;
                }

                $sciName = $item['canonicalName'] ?? $item['scientificName'] ?? '';
                if (! $sciName) {
                    continue;
                }

                try {
                    $syncResult = $importService->syncByScientificName($sciName, $strict, $fetchVernacular);

                    if ($syncResult['success']) {
                        $results['synced'][] = [
                            'taxon_id' => $syncResult['taxon']->id,
                            'name'     => $syncResult['taxon']->binomial_name,
                            'created'  => $syncResult['created'],
                        ];
                    } else {
                        $results['errors'][] = "{$sciName}: {$syncResult['message']}";
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "{$sciName}: {$e->getMessage()}";
                }
            }
        }

        return response()->json([
            'success'      => true,
            'mode'         => $mode,
            'query'        => $query,
            'synced_count' => count($results['synced']),
            'error_count'  => count($results['errors']),
            'synced'       => $results['synced'],
            'errors'       => array_slice($results['errors'], 0, 10),
        ]);
    }

    /**
     * Import all species from a GBIF family.
     *
     * POST /api/v1/taxons/import-family
     */
    public function importFamily(Request $request, GbifImportService $importService): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        $data = $request->validate([
            'family_name'   => ['required', 'string', 'min:2'],
            'accepted_only' => ['nullable', 'boolean'],
            'import_limit'  => ['nullable', 'integer', 'min:1', 'max:5000'],
            'dry_run'       => ['nullable', 'boolean'],
        ]);

        $result = $importService->importFamilySpecies(
            $data['family_name'],
            $data['accepted_only'] ?? true,
            $data['import_limit'] ?? 1000,
            $data['dry_run'] ?? false,
        );

        return response()->json($result);
    }

    /**
     * Sync a single existing taxon from GBIF.
     *
     * POST /api/v1/taxons/{id}/sync-from-gbif
     */
    public function syncSingleFromGbif(int $id, GbifImportService $importService, GbifService $gbifService): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        $taxon = Taxon::findOrFail($id);

        // Strategy 1: Use existing gbif_id
        if ($taxon->gbif_id) {
            $gbifDetails = $gbifService->getTaxon($taxon->gbif_id);

            if ($gbifDetails) {
                [$updated, $created, $stats] = $importService->upsertFromGbif($gbifDetails, true);

                return response()->json([
                    'success'  => true,
                    'strategy' => 'gbif_id',
                    'taxon'    => $updated,
                    'stats'    => $stats,
                ]);
            }
        }

        // Strategy 2: Backbone match on scientific name
        $scientificName = $taxon->binomial_name ?: "{$taxon->genus} {$taxon->species}";

        if ($scientificName) {
            $syncResult = $importService->syncByScientificName($scientificName, false, true);

            if ($syncResult['success']) {
                return response()->json([
                    'success'  => true,
                    'strategy' => 'backbone_match',
                    'taxon'    => $syncResult['taxon'],
                    'created'  => $syncResult['created'],
                    'message'  => $syncResult['message'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $syncResult['message'],
            ], 404);
        }

        return response()->json([
            'success' => false,
            'message' => 'Impossible de synchroniser: pas de nom scientifique ni de gbif_id.',
        ], 400);
    }

    /**
     * Bulk sync selected taxons from GBIF backbone.
     *
     * POST /api/v1/taxons/bulk-sync-gbif
     */
    public function bulkSyncGbif(Request $request, GbifImportService $importService): JsonResponse
    {
        if (! Auth::user()->is_staff) {
            return response()->json(['detail' => 'Acces reserve au personnel.'], 403);
        }

        $data = $request->validate([
            'taxon_ids'        => ['required', 'array', 'min:1', 'max:100'],
            'taxon_ids.*'      => ['integer', 'exists:taxons,id'],
            'fetch_vernacular' => ['nullable', 'boolean'],
        ]);

        $fetchVernacular = $data['fetch_vernacular'] ?? true;
        $results = ['synced' => [], 'errors' => []];

        foreach ($data['taxon_ids'] as $taxonId) {
            $taxon = Taxon::find($taxonId);
            if (! $taxon) {
                continue;
            }

            $scientificName = $taxon->binomial_name ?: "{$taxon->genus} {$taxon->species}";

            try {
                $syncResult = $importService->syncByScientificName($scientificName, false, $fetchVernacular);

                if ($syncResult['success']) {
                    $results['synced'][] = [
                        'taxon_id' => $syncResult['taxon']->id,
                        'name'     => $syncResult['taxon']->binomial_name,
                        'created'  => $syncResult['created'],
                    ];
                } else {
                    $results['errors'][] = "{$scientificName}: {$syncResult['message']}";
                }
            } catch (\Exception $e) {
                $results['errors'][] = "{$scientificName}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'success'      => true,
            'synced_count' => count($results['synced']),
            'error_count'  => count($results['errors']),
            'synced'       => $results['synced'],
            'errors'       => array_slice($results['errors'], 0, 10),
        ]);
    }
}
