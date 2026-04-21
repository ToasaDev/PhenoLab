<?php

namespace App\Console\Commands;

use App\Models\Taxon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeedFruitTaxonsCommand extends Command
{
    protected $signature = 'taxons:seed-botanical';
    protected $description = 'Seed missing botanical taxons (fruits, roses, ornamentals) from GBIF for cultivar import support';

    /**
     * Fruit taxons needed for EUPVP cultivar mapping.
     * Format: binomial_name => [genus, species, family, common_name_fr]
     * For genus-level entries, species is null.
     */
    private const FRUIT_TAXONS = [
        'Prunus armeniaca'      => ['Prunus', 'armeniaca', 'Rosaceae', 'Abricotier'],
        'Prunus persica'        => ['Prunus', 'persica', 'Rosaceae', 'Pêcher'],
        'Prunus avium'          => ['Prunus', 'avium', 'Rosaceae', 'Cerisier'],
        'Prunus cerasus'        => ['Prunus', 'cerasus', 'Rosaceae', 'Griottier'],
        'Prunus domestica'      => ['Prunus', 'domestica', 'Rosaceae', 'Prunier'],
        'Prunus dulcis'         => ['Prunus', 'dulcis', 'Rosaceae', 'Amandier'],
        'Prunus salicina'       => ['Prunus', 'salicina', 'Rosaceae', 'Prunier japonais'],
        'Prunus'                => ['Prunus', null, 'Rosaceae', 'Prunus'],
        'Fragaria × ananassa'   => ['Fragaria', '× ananassa', 'Rosaceae', 'Fraisier cultivé'],
        'Fragaria'              => ['Fragaria', null, 'Rosaceae', 'Fraisier'],
        'Fragaria vesca'        => ['Fragaria', 'vesca', 'Rosaceae', 'Fraisier des bois'],
        'Rubus idaeus'          => ['Rubus', 'idaeus', 'Rosaceae', 'Framboisier'],
        'Rubus fruticosus'      => ['Rubus', 'fruticosus', 'Rosaceae', 'Mûrier sauvage'],
        'Rubus'                 => ['Rubus', null, 'Rosaceae', 'Ronce'],
        'Ribes nigrum'          => ['Ribes', 'nigrum', 'Grossulariaceae', 'Cassissier'],
        'Ribes rubrum'          => ['Ribes', 'rubrum', 'Grossulariaceae', 'Groseillier rouge'],
        'Ribes uva-crispa'      => ['Ribes', 'uva-crispa', 'Grossulariaceae', 'Groseillier à maquereau'],
        'Ribes'                 => ['Ribes', null, 'Grossulariaceae', 'Groseillier'],
        'Vaccinium corymbosum'  => ['Vaccinium', 'corymbosum', 'Ericaceae', 'Myrtillier américain'],
        'Vaccinium'             => ['Vaccinium', null, 'Ericaceae', 'Myrtille'],
        'Ficus carica'          => ['Ficus', 'carica', 'Moraceae', 'Figuier'],
        'Olea europaea'         => ['Olea', 'europaea', 'Oleaceae', 'Olivier'],
        'Juglans regia'         => ['Juglans', 'regia', 'Juglandaceae', 'Noyer commun'],
        'Castanea sativa'       => ['Castanea', 'sativa', 'Fagaceae', 'Châtaignier'],
        'Castanea'              => ['Castanea', null, 'Fagaceae', 'Châtaignier'],
        'Citrus sinensis'       => ['Citrus', 'sinensis', 'Rutaceae', 'Oranger'],
        'Citrus reticulata'     => ['Citrus', 'reticulata', 'Rutaceae', 'Mandarinier'],
        'Citrus limon'          => ['Citrus', 'limon', 'Rutaceae', 'Citronnier'],
        'Citrus paradisi'       => ['Citrus', 'paradisi', 'Rutaceae', 'Pamplemoussier'],
        'Citrus clementina'     => ['Citrus', 'clementina', 'Rutaceae', 'Clémentinier'],
        'Citrus'                => ['Citrus', null, 'Rutaceae', 'Agrume'],
        'Corylus avellana'      => ['Corylus', 'avellana', 'Betulaceae', 'Noisetier'],
        'Cydonia oblonga'       => ['Cydonia', 'oblonga', 'Rosaceae', 'Cognassier'],
        'Pistacia vera'         => ['Pistacia', 'vera', 'Anacardiaceae', 'Pistachier'],
        'Actinidia'             => ['Actinidia', null, 'Actinidiaceae', 'Kiwi'],
        'Pyrus pyrifolia'       => ['Pyrus', 'pyrifolia', 'Rosaceae', 'Poirier asiatique'],
        // Rosiers
        'Rosa'                  => ['Rosa', null, 'Rosaceae', 'Rosier'],
        'Rosa canina'           => ['Rosa', 'canina', 'Rosaceae', 'Églantier'],
        'Rosa gallica'          => ['Rosa', 'gallica', 'Rosaceae', 'Rosier de France'],
        'Rosa damascena'        => ['Rosa', 'damascena', 'Rosaceae', 'Rosier de Damas'],
        'Rosa centifolia'       => ['Rosa', 'centifolia', 'Rosaceae', 'Rose chou'],
        'Rosa moschata'         => ['Rosa', 'moschata', 'Rosaceae', 'Rosier musqué'],
        'Rosa rugosa'           => ['Rosa', 'rugosa', 'Rosaceae', 'Rosier rugueux'],
        'Rosa multiflora'       => ['Rosa', 'multiflora', 'Rosaceae', 'Rosier multiflore'],
        'Rosa wichuraiana'      => ['Rosa', 'wichuraiana', 'Rosaceae', 'Rosier de Wichura'],
        'Rosa chinensis'        => ['Rosa', 'chinensis', 'Rosaceae', 'Rosier de Chine'],
        'Rosa banksiae'         => ['Rosa', 'banksiae', 'Rosaceae', 'Rosier de Banks'],
        // Autres ornementales
        'Hydrangea'             => ['Hydrangea', null, 'Hydrangeaceae', 'Hortensia'],
        'Hydrangea macrophylla' => ['Hydrangea', 'macrophylla', 'Hydrangeaceae', 'Hortensia'],
        'Hydrangea paniculata'  => ['Hydrangea', 'paniculata', 'Hydrangeaceae', 'Hortensia paniculé'],
        'Lavandula'             => ['Lavandula', null, 'Lamiaceae', 'Lavande'],
        'Lavandula angustifolia'=> ['Lavandula', 'angustifolia', 'Lamiaceae', 'Lavande vraie'],
        'Camellia japonica'     => ['Camellia', 'japonica', 'Theaceae', 'Camélia du Japon'],
        'Camellia'              => ['Camellia', null, 'Theaceae', 'Camélia'],
        'Wisteria'              => ['Wisteria', null, 'Fabaceae', 'Glycine'],
        'Syringa'               => ['Syringa', null, 'Oleaceae', 'Lilas'],
        'Syringa vulgaris'      => ['Syringa', 'vulgaris', 'Oleaceae', 'Lilas commun'],
    ];

    public function handle(): int
    {
        $created = 0;
        $skipped = 0;

        foreach (self::FRUIT_TAXONS as $binomial => [$genus, $species, $family, $commonFr]) {
            // Skip if already exists
            if (Taxon::where('binomial_name', $binomial)->exists()) {
                $this->line("  exists: {$binomial}");
                $skipped++;
                continue;
            }

            // Fetch GBIF ID
            $gbifId = $this->fetchGbifId($binomial, $genus, $species);

            if (! $gbifId) {
                $this->warn("  no GBIF match: {$binomial} — using generated ID");
                $gbifId = null;
            }

            $taxonIdStr = $gbifId ? "GBIF:{$gbifId}" : "LOCAL:" . strtoupper(str_replace(' ', '_', $binomial));

            // Ensure taxon_id is unique
            if (Taxon::where('taxon_id', $taxonIdStr)->exists()) {
                $taxonIdStr .= '_' . time();
            }

            // If gbif_id already used by another taxon, set to null to avoid unique constraint
            $gbifIdForDb = $gbifId;
            if ($gbifId && Taxon::where('gbif_id', $gbifId)->exists()) {
                $this->warn("  GBIF ID {$gbifId} already used — storing without gbif_id");
                $gbifIdForDb = null;
            }

            Taxon::create([
                'taxon_id'       => $taxonIdStr,
                'kingdom'        => 'Plantae',
                'phylum'         => 'Tracheophyta',
                'class_name'     => 'Magnoliopsida',
                'family'         => $family,
                'genus'          => $genus,
                'species'        => $species ?? $genus,
                'binomial_name'  => $binomial,
                'common_name_fr' => $commonFr,
                'gbif_id'        => $gbifIdForDb,
                'gbif_status'    => $gbifId ? 'ACCEPTED' : null,
                'gbif_rank'      => $species ? 'SPECIES' : 'GENUS',
                'gbif_canonical_name' => $binomial,
                'gbif_synced_at' => $gbifId ? now() : null,
            ]);

            $this->info("  created: {$binomial} (taxon_id: {$taxonIdStr})");
            $created++;
        }

        $this->newLine();
        $this->info("Done. Created: {$created} | Skipped (existing): {$skipped}");

        // Show cultivar mapping improvement
        $totalCultivars = \App\Models\Cultivar::count();
        $mappedBefore = \App\Models\Cultivar::whereNotNull('taxon_id')->count();

        $this->info("Cultivars total: {$totalCultivars} | Currently mapped: {$mappedBefore}");
        $this->info("Run 'php artisan cultivars:import <file> --clear' to re-import with updated taxon mappings.");

        return 0;
    }

    private function fetchGbifId(string $binomial, string $genus, ?string $species): ?int
    {
        try {
            $response = Http::timeout(10)->get('https://api.gbif.org/v1/species/match', [
                'name'    => $binomial,
                'kingdom' => 'Plantae',
                'strict'  => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['matchType'] ?? '') !== 'NONE' && isset($data['usageKey'])) {
                    return (int) $data['usageKey'];
                }
            }
        } catch (\Exception $e) {
            $this->warn("  GBIF API error for {$binomial}: {$e->getMessage()}");
        }

        return null;
    }
}
