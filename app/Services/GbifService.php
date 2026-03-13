<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GBIF (Global Biodiversity Information Facility) API Integration Service.
 *
 * Port of Django's apps/plants/services/gbif.py
 */
class GbifService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('phenolab.gbif.api_base_url', 'https://api.gbif.org/v1');
        $this->timeout = config('phenolab.gbif.timeout', 30);
    }

    /**
     * Search GBIF species/taxa by query string.
     */
    public function searchTaxa(string $q, int $limit = 20, int $offset = 0, ?string $rank = null): array
    {
        $q = $this->normalizeString($q);
        if (! $q) {
            return ['results' => [], 'count' => 0, 'offset' => 0, 'limit' => $limit];
        }

        $params = [
            'q' => $q,
            'limit' => min(max(1, $limit), 1000),
            'offset' => max(0, $offset),
        ];

        if ($rank) {
            $params['rank'] = strtoupper($rank);
        }

        $response = $this->makeRequest('/species/search', $params);

        if (! $response) {
            return ['results' => [], 'count' => 0, 'offset' => $offset, 'limit' => $limit];
        }

        return [
            'results' => $response['results'] ?? [],
            'count' => $response['count'] ?? 0,
            'offset' => $response['offset'] ?? $offset,
            'limit' => $response['limit'] ?? $limit,
            'endOfRecords' => $response['endOfRecords'] ?? true,
        ];
    }

    /**
     * Get detailed information for a specific GBIF taxon by ID.
     */
    public function getTaxon(int $gbifId): ?array
    {
        if ($gbifId <= 0) {
            return null;
        }

        return $this->makeRequest("/species/{$gbifId}");
    }

    /**
     * Match a scientific name against GBIF Backbone Taxonomy.
     */
    public function backboneMatch(string $name, bool $strict = false, ?string $rank = null): ?array
    {
        $name = $this->normalizeString($name);
        if (! $name) {
            return null;
        }

        $params = [
            'name' => $name,
            'strict' => $strict ? 'true' : 'false',
        ];

        if ($rank) {
            $params['rank'] = strtoupper($rank);
        }

        $response = $this->makeRequest('/species/match', $params);

        if (! $response) {
            return null;
        }

        $matchType = $response['matchType'] ?? 'NONE';
        if ($matchType === 'NONE') {
            Log::info("No GBIF backbone match for: {$name}");
            return null;
        }

        return $response;
    }

    /**
     * Get vernacular (common) names for a GBIF taxon.
     */
    public function getVernacularNames(int $gbifId, int $limit = 200): array
    {
        if ($gbifId <= 0) {
            return [];
        }

        $response = $this->makeRequest("/species/{$gbifId}/vernacularNames", [
            'limit' => min(max(1, $limit), 1000),
        ]);

        if (! $response) {
            return [];
        }

        return $response['results'] ?? [];
    }

    /**
     * Aggregate all vernacular names for a given language into a single string.
     */
    public function aggregateVernacularNames(array $vernacularNames, string $language): ?string
    {
        $canonicalLang = $this->normalizeLanguageCode($language);
        if (! $canonicalLang) {
            return null;
        }

        $collected = [];

        foreach ($vernacularNames as $item) {
            $name = trim($item['vernacularName'] ?? '');
            $itemLang = $this->normalizeLanguageCode($item['language'] ?? '');
            $preferred = $item['preferred'] ?? false;

            if (! $name || $itemLang !== $canonicalLang) {
                continue;
            }

            $collected[] = [
                'name' => $this->normalizeString($name),
                'preferred' => $preferred,
            ];
        }

        if (empty($collected)) {
            return null;
        }

        // Deduplicate (case-insensitive)
        $seen = [];
        $unique = [];
        foreach ($collected as $item) {
            $lower = mb_strtolower($item['name']);
            if (! isset($seen[$lower])) {
                $seen[$lower] = true;
                $unique[] = $item;
            }
        }

        // Sort: preferred first, then alphabetical
        usort($unique, function ($a, $b) {
            if ($a['preferred'] !== $b['preferred']) {
                return $b['preferred'] <=> $a['preferred'];
            }
            return strcmp($a['name'], $b['name']);
        });

        return implode(', ', array_column($unique, 'name'));
    }

    /**
     * Make a GET request to GBIF API.
     */
    private function makeRequest(string $endpoint, array $params = []): ?array
    {
        $url = $this->baseUrl . $endpoint;

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url, $params);

            if ($response->failed()) {
                Log::error("GBIF API HTTP error for {$endpoint}: {$response->status()}");
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("GBIF API request error for {$endpoint}: {$e->getMessage()}");
            return null;
        }
    }

    private function normalizeString(?string $text): string
    {
        if (! $text) {
            return '';
        }
        return preg_replace('/\s+/', ' ', trim($text));
    }

    private function normalizeLanguageCode(?string $lang): ?string
    {
        if (! $lang) {
            return null;
        }

        $map = [
            'fr' => 'fr', 'fra' => 'fr', 'fre' => 'fr',
            'en' => 'en', 'eng' => 'en',
            'it' => 'it', 'ita' => 'it',
        ];

        return $map[strtolower(trim($lang))] ?? null;
    }
}
