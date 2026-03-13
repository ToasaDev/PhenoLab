<?php

namespace App\Services;

use App\Models\Taxon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GBIF Import Service - Upsert logic for syncing GBIF taxonomic data.
 *
 * Port of Django's apps/plants/services/gbif_import.py
 */
class GbifImportService
{
    public const ALLOWED_RANKS = ['SPECIES', 'SUBSPECIES', 'VARIETY', 'FORM', 'SUBVARIETY'];

    private GbifService $gbif;

    public function __construct(GbifService $gbif)
    {
        $this->gbif = $gbif;
    }

    /**
     * Sync a taxon by scientific name using GBIF backbone matching.
     *
     * @return array{taxon: ?Taxon, success: bool, created: bool, message: string}
     */
    public function syncByScientificName(string $scientificName, bool $strict = false, bool $fetchVernacular = true): array
    {
        $result = ['taxon' => null, 'success' => false, 'created' => false, 'message' => ''];

        try {
            $match = $this->gbif->backboneMatch($scientificName, $strict);

            if (! $match) {
                $result['message'] = "Aucune correspondance GBIF pour '{$scientificName}'";
                return $result;
            }

            $gbifId = $match['usageKey'] ?? null;
            if (! $gbifId) {
                $result['message'] = "Correspondance GBIF pour '{$scientificName}' sans usageKey";
                return $result;
            }

            $gbifDetails = $this->gbif->getTaxon($gbifId);
            if (! $gbifDetails) {
                $result['message'] = "Impossible de recuperer les details GBIF pour ID {$gbifId}";
                return $result;
            }

            [$taxon, $created, $stats] = $this->upsertFromGbif($gbifDetails, $fetchVernacular);

            $result['taxon'] = $taxon;
            $result['success'] = true;
            $result['created'] = $created;
            $result['message'] = ($created ? 'Cree' : 'Mis a jour') . ": {$taxon->binomial_name}";
            $result['stats'] = $stats;

            return $result;
        } catch (\Exception $e) {
            $result['message'] = "Erreur pour '{$scientificName}': {$e->getMessage()}";
            Log::error($result['message'], ['exception' => $e]);
            return $result;
        }
    }

    /**
     * Create or update a Taxon from GBIF data with safe upsert logic.
     *
     * @return array{0: Taxon, 1: bool, 2: array}
     */
    public function upsertFromGbif(array $gbifData, bool $fetchVernacular = true, bool $forceUpdate = false): array
    {
        $stats = ['updated_fields' => 0, 'skipped_fields' => 0];

        $gbifId = $gbifData['key'] ?? $gbifData['usageKey'] ?? null;
        if (! $gbifId) {
            throw new \InvalidArgumentException("GBIF data must contain 'key' or 'usageKey'");
        }

        $rank = strtoupper($gbifData['rank'] ?? '');
        if (! in_array($rank, self::ALLOWED_RANKS)) {
            throw new \InvalidArgumentException(
                "Seuls les taxons au niveau espece peuvent etre importes. Rang recu: '{$rank}'"
            );
        }

        $mapped = $this->mapGbifToTaxonFields($gbifData);
        $genus = $mapped['genus'] ?? '';
        $species = $mapped['species'] ?? '';

        if (! $genus || ! $species) {
            throw new \InvalidArgumentException('Les donnees GBIF doivent contenir genus et species');
        }

        return DB::transaction(function () use ($gbifId, $mapped, $genus, $species, $fetchVernacular, $forceUpdate, &$stats) {
            $existing = $this->findExistingTaxon($gbifId, $genus, $species);

            if ($existing) {
                $created = false;

                // Always update GBIF-specific fields
                $existing->gbif_id = $gbifId;
                $existing->gbif_status = $mapped['gbif_status'] ?? '';
                $existing->gbif_rank = $mapped['gbif_rank'] ?? '';
                $existing->gbif_canonical_name = $mapped['gbif_canonical_name'] ?? '';
                $existing->gbif_synced_at = now();
                $stats['updated_fields'] += 5;

                $taxonomicFields = ['kingdom', 'phylum', 'class_name', 'order', 'family', 'author', 'publication_year'];
                foreach ($taxonomicFields as $field) {
                    if (isset($mapped[$field])) {
                        if (! $forceUpdate && $existing->{$field}) {
                            $stats['skipped_fields']++;
                        } else {
                            $existing->{$field} = $mapped[$field];
                            $stats['updated_fields']++;
                        }
                    }
                }

                $taxon = $existing;
            } else {
                $created = true;
                $taxonId = "GBIF:{$gbifId}";

                if (Taxon::where('taxon_id', $taxonId)->exists()) {
                    $taxonId = "GBIF:{$gbifId}_" . time();
                }

                $taxon = new Taxon();
                $taxon->taxon_id = $taxonId;

                foreach ($mapped as $field => $value) {
                    $taxon->{$field} = $value;
                }
                $stats['updated_fields'] += count($mapped);
            }

            // Fetch vernacular names
            if ($fetchVernacular) {
                $vernacular = $this->fetchVernacularNames($gbifId);
                foreach ($vernacular as $field => $value) {
                    if ($value) {
                        if (! $forceUpdate && $taxon->{$field}) {
                            $stats['skipped_fields']++;
                        } else {
                            $taxon->{$field} = $value;
                            $stats['updated_fields']++;
                        }
                    }
                }
            }

            $taxon->save();

            return [$taxon, $created, $stats];
        });
    }

    /**
     * Import all species-level taxa from GBIF for a given family.
     */
    public function importFamilySpecies(string $familyName, bool $acceptedOnly = true, int $limit = 1000, bool $dryRun = false): array
    {
        $maxLimit = 5000;
        $batchSize = 100;
        $limit = min($limit, $maxLimit);

        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        $errorLog = [];
        $offset = 0;
        $totalProcessed = 0;
        $totalAvailable = 0;

        try {
            while ($totalProcessed < $limit) {
                $batch = $this->gbif->searchTaxa($familyName, $batchSize, $offset);

                if (empty($batch['results'])) {
                    break;
                }

                if ($offset === 0) {
                    $totalAvailable = $batch['count'] ?? 0;
                }

                foreach ($batch['results'] as $item) {
                    if ($totalProcessed >= $limit) {
                        break;
                    }

                    $rank = strtoupper($item['rank'] ?? '');
                    $family = $item['family'] ?? '';
                    $status = $item['taxonomicStatus'] ?? '';
                    $sciName = $item['canonicalName'] ?? $item['scientificName'] ?? '';

                    if (strtolower($family) !== strtolower($familyName)) {
                        $results['skipped']++;
                        continue;
                    }

                    if (! in_array($rank, self::ALLOWED_RANKS)) {
                        $results['skipped']++;
                        continue;
                    }

                    if ($acceptedOnly && $status !== 'ACCEPTED') {
                        $results['skipped']++;
                        continue;
                    }

                    if (! $sciName) {
                        $results['skipped']++;
                        continue;
                    }

                    $totalProcessed++;

                    if (! $dryRun) {
                        try {
                            $syncResult = $this->syncByScientificName($sciName, false, true);

                            if ($syncResult['success']) {
                                $syncResult['created'] ? $results['created']++ : $results['updated']++;
                            } else {
                                $results['errors']++;
                                $errorLog[] = "{$sciName}: {$syncResult['message']}";
                            }
                        } catch (\Exception $e) {
                            $results['errors']++;
                            $errorLog[] = "{$sciName}: {$e->getMessage()}";
                        }
                    } else {
                        $results['created']++;
                    }
                }

                $offset += $batchSize;

                if (count($batch['results']) < $batchSize) {
                    break;
                }
            }

            return [
                'success' => true,
                'family' => $familyName,
                'results' => $results,
                'errors' => array_slice($errorLog, 0, 10),
                'total_processed' => $totalProcessed,
                'total_available' => $totalAvailable,
                'dry_run' => $dryRun,
            ];
        } catch (\Exception $e) {
            Log::error("Fatal error during family import for {$familyName}: {$e->getMessage()}");
            return [
                'success' => false,
                'family' => $familyName,
                'results' => $results,
                'errors' => ["Fatal: {$e->getMessage()}"] + array_slice($errorLog, 0, 9),
                'total_processed' => $totalProcessed,
                'total_available' => $totalAvailable,
                'dry_run' => $dryRun,
            ];
        }
    }

    /**
     * Map GBIF API response to Taxon model fields.
     */
    private function mapGbifToTaxonFields(array $gbifData): array
    {
        $fields = [];

        if (isset($gbifData['key'])) {
            $fields['gbif_id'] = $gbifData['key'];
        }
        if (isset($gbifData['taxonomicStatus'])) {
            $fields['gbif_status'] = $gbifData['taxonomicStatus'];
        }
        if (isset($gbifData['rank'])) {
            $fields['gbif_rank'] = $gbifData['rank'];
        }
        if (isset($gbifData['canonicalName'])) {
            $fields['gbif_canonical_name'] = $gbifData['canonicalName'];
        }

        $fields['gbif_synced_at'] = now();

        foreach (['kingdom', 'phylum', 'order', 'family', 'genus'] as $field) {
            if (! empty($gbifData[$field])) {
                $fields[$field] = $gbifData[$field];
            }
        }

        if (! empty($gbifData['class'])) {
            $fields['class_name'] = $gbifData['class'];
        }

        if (! empty($gbifData['species'])) {
            $speciesFull = $gbifData['species'];
            $fields['species'] = str_contains($speciesFull, ' ')
                ? explode(' ', $speciesFull, 2)[1]
                : $speciesFull;
        }

        if (! empty($gbifData['authorship'])) {
            $fields['author'] = $gbifData['authorship'];
        }

        if (! empty($gbifData['publishedIn'])) {
            if (preg_match('/\b(1[7-9]\d{2}|20\d{2})\b/', $gbifData['publishedIn'], $m)) {
                $fields['publication_year'] = (int) $m[1];
            }
        }

        return $fields;
    }

    /**
     * Fetch vernacular names from GBIF and select best for EN/FR/IT.
     */
    private function fetchVernacularNames(int $gbifId): array
    {
        $names = ['common_name_en' => '', 'common_name_fr' => '', 'common_name_it' => ''];

        try {
            $vernacular = $this->gbif->getVernacularNames($gbifId);

            if (empty($vernacular)) {
                return $names;
            }

            foreach (['en' => 'common_name_en', 'fr' => 'common_name_fr', 'it' => 'common_name_it'] as $lang => $field) {
                $best = $this->gbif->aggregateVernacularNames($vernacular, $lang);
                if ($best) {
                    $names[$field] = $best;
                }
            }
        } catch (\Exception $e) {
            Log::error("Error fetching vernacular names for GBIF ID {$gbifId}: {$e->getMessage()}");
        }

        return $names;
    }

    /**
     * Find existing Taxon using 3-tier matching strategy.
     */
    private function findExistingTaxon(?int $gbifId, string $genus, string $species): ?Taxon
    {
        // Tier 1: Match by GBIF ID
        if ($gbifId) {
            $taxon = Taxon::where('gbif_id', $gbifId)->first();
            if ($taxon) {
                return $taxon;
            }
        }

        // Tier 2: Match by genus + species
        if ($genus && $species) {
            $taxon = Taxon::where('genus', $genus)->where('species', $species)->first();
            if ($taxon) {
                return $taxon;
            }
        }

        // Tier 3: Match by binomial_name
        $binomial = "{$genus} {$species}";
        return Taxon::where('binomial_name', $binomial)->first();
    }
}
