<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Observation;
use App\Models\PhenologicalStage;
use App\Models\Plant;
use App\Models\PlantAction;
use App\Models\PlantActionType;
use App\Models\PlantLayerPosition;
use App\Models\Site;
use App\Models\SitePlanLayer;
use App\Models\Taxon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExportService
{
    private array $filters;
    private ?object $user;
    private string $tmpDir;
    private array $counts = [
        'sites' => 0,
        'plants' => 0,
        'observations' => 0,
        'plant_photos' => 0,
        'observation_photos' => 0,
        'site_plans' => 0,
        'categories' => 0,
        'taxons' => 0,
        'stages' => 0,
        'layers' => 0,
        'plant_actions' => 0,
    ];

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->user = Auth::user();
    }

    // ── Filename helpers ────────────────────────────────────

    /**
     * Sanitize a string for use in filenames: ASCII, lowercase, hyphens.
     */
    private function sanitize(?string $value, int $maxLen = 40): string
    {
        if (! $value) {
            return 'sans-nom';
        }

        $clean = Str::ascii($value);
        $clean = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $clean);
        $clean = preg_replace('/\s+/', '-', trim($clean));
        $clean = strtolower($clean);

        return Str::limit($clean, $maxLen, '');
    }

    /**
     * Build a human-readable photo filename.
     * Example: plante_araucaria-laubenfelsii_site_ouaoue_photo_42_principale.jpg
     */
    private function plantPhotoFilename(Plant $plant, $photo): string
    {
        $ext = $photo->image ? (pathinfo($photo->image, PATHINFO_EXTENSION) ?: 'jpg') : 'jpg';
        $plantName = $this->sanitize($plant->name, 30);
        $siteName = $this->sanitize($plant->site?->name, 20);
        $suffix = $photo->is_main_photo ? '_principale' : '';

        return "plante_{$plantName}_site_{$siteName}_photo_{$photo->id}{$suffix}.{$ext}";
    }

    /**
     * Build a human-readable observation photo filename.
     * Example: obs_2026-03-15_araucaria-laubenfelsii_ouaoue_photo_7.jpg
     */
    private function obsPhotoFilename(Observation $obs, $photo): string
    {
        $ext = $photo->image ? (pathinfo($photo->image, PATHINFO_EXTENSION) ?: 'jpg') : 'jpg';
        $date = $obs->observation_date?->format('Y-m-d') ?? 'sans-date';
        $plantName = $this->sanitize($obs->plant?->name, 25);
        $siteName = $this->sanitize($obs->plant?->site?->name, 20);

        return "obs_{$date}_{$plantName}_{$siteName}_photo_{$photo->id}.{$ext}";
    }

    /**
     * Build a human-readable site plan filename.
     * Example: plan_ouaoue_site_1.png
     */
    private function sitePlanFilename(Site $site): string
    {
        $ext = $site->site_plan_image ? (pathinfo($site->site_plan_image, PATHINFO_EXTENSION) ?: 'png') : 'png';
        $siteName = $this->sanitize($site->name, 30);

        return "plan_{$siteName}_site_{$site->id}.{$ext}";
    }

    /**
     * Build a human-readable layer plan filename.
     * Example: couche_ouaoue_2025-01_a_2025-12_couche_3.svg
     */
    private function layerPlanFilename(SitePlanLayer $layer): string
    {
        $siteName = $this->sanitize($layer->site?->name, 25);
        $dateFrom = $layer->start_date?->format('Y-m') ?? 'debut';
        $dateTo = $layer->end_date?->format('Y-m') ?? 'en-cours';
        $layerName = $this->sanitize($layer->name, 20);

        return "couche_{$siteName}_{$dateFrom}_a_{$dateTo}_{$layerName}_id{$layer->id}.svg";
    }

    // ── Main export ─────────────────────────────────────────

    /**
     * Build and return the path to the export ZIP file.
     */
    public function export(string $format = 'full'): string
    {
        $this->tmpDir = sys_get_temp_dir() . '/phenolab_export_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        // Collect data
        $sites = $this->getSites();
        $plants = $this->getPlants();
        $observations = $this->getObservations();
        $categories = Category::orderBy('name')->get();
        $taxons = $this->getTaxons($plants);
        $stages = PhenologicalStage::orderBy('stage_code')->get();

        // Collect layers for exported sites
        $siteIds = $sites->pluck('id');
        $layers = SitePlanLayer::with(['site:id,name,site_plan_image', 'positions.plant:id,name'])
            ->whereIn('site_id', $siteIds)
            ->orderBy('site_id')
            ->orderBy('start_date')
            ->get();

        // Collect plant actions
        $plantActions = $this->getPlantActions($plants);

        $this->counts['sites'] = $sites->count();
        $this->counts['plants'] = $plants->count();
        $this->counts['observations'] = $observations->count();
        $this->counts['categories'] = $categories->count();
        $this->counts['taxons'] = $taxons->count();
        $this->counts['stages'] = $stages->count();
        $this->counts['layers'] = $layers->count();
        $this->counts['plant_actions'] = $plantActions->count();

        // CSV exports
        if ($format === 'full' || $format === 'csv') {
            $this->writeSitesCsv($sites);
            $this->writePlantsCsv($plants);
            $this->writeObservationsCsv($observations);
            $this->writeCategoriesCsv($categories);
            $this->writeTaxonsCsv($taxons);
            $this->writeStagesCsv($stages);
            $this->writeLayersCsv($layers);
            $this->writeActionsCsv($plantActions);
        }

        // JSON exports
        if ($format === 'full' || $format === 'json') {
            mkdir($this->tmpDir . '/json', 0755, true);
            $this->writeSitesJson($sites);
            $this->writePlantsJson($plants);
            $this->writeObservationsJson($observations);
            $this->writeCategoriesJson($categories);
            $this->writeTaxonsJson($taxons);
            $this->writeStagesJson($stages);
            $this->writeLayersJson($layers);
            $this->writeActionsJson($plantActions);
        }

        // Images, plans and database
        if ($format === 'full') {
            mkdir($this->tmpDir . '/images/plants', 0755, true);
            mkdir($this->tmpDir . '/images/observations', 0755, true);
            mkdir($this->tmpDir . '/site_plans', 0755, true);
            $this->exportPlantPhotos($plants);
            $this->exportObservationPhotos($observations);
            $this->exportSitePlans($sites);
            $this->exportLayerPlans($layers);

            // Database backup (always full, compatible MySQL)
            mkdir($this->tmpDir . '/database', 0755, true);
            $this->exportDatabase();
        }

        // Metadata
        mkdir($this->tmpDir . '/metadata', 0755, true);
        $this->writeManifest();
        $this->writeReadme($format);

        // Build ZIP
        $zipPath = sys_get_temp_dir() . '/phenolab_export_' . now()->format('Y-m-d_H-i') . '.zip';
        $this->buildZip($zipPath);

        // Cleanup temp directory
        $this->deleteDirectory($this->tmpDir);

        return $zipPath;
    }

    // ── Queries with visibility ─────────────────────────────

    private function getSites(): \Illuminate\Support\Collection
    {
        $query = Site::with('owner:id,name');

        if (! $this->user?->is_staff) {
            $query->where(function (Builder $q) {
                $q->where('is_private', false);
                if ($this->user) {
                    $q->orWhere('owner_id', $this->user->id);
                }
            });
        }

        if (! empty($this->filters['site_id'])) {
            $query->where('id', $this->filters['site_id']);
        }

        return $query->orderBy('name')->get();
    }

    private function getPlants(): \Illuminate\Support\Collection
    {
        $query = Plant::with([
            'taxon:id,binomial_name,common_name_fr,family,genus,species',
            'category:id,name,icon,category_type',
            'site:id,name',
            'owner:id,name',
            'mainPhoto:id,plant_id,image,is_main_photo',
            'photos:id,plant_id,image,title,photo_type,is_main_photo',
            'cultivationProfile',
            'cultivarRef:id,name,upov_code',
            'userTags:user_plant_tags.id,name,color',
        ])->withCount('observations', 'photos');

        // Visibility
        if (! $this->user?->is_staff) {
            $query->where(function (Builder $q) {
                $q->where('is_private', false)
                  ->whereHas('site', fn (Builder $s) => $s->where('is_private', false));
                if ($this->user) {
                    $q->orWhere('owner_id', $this->user->id);
                }
            });
        }

        // Filters
        if (! empty($this->filters['site_id'])) {
            $query->where('site_id', $this->filters['site_id']);
        }
        if (! empty($this->filters['category'])) {
            $query->where('category_id', $this->filters['category']);
        }
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (! empty($this->filters['taxon'])) {
            $query->where('taxon_id', $this->filters['taxon']);
        }
        // year filter: skip if "all"
        if (! empty($this->filters['year']) && $this->filters['year'] !== 'all') {
            $query->whereHas('observations', function (Builder $obs) {
                $obs->whereYear('observation_date', $this->filters['year']);
            });
        }

        return $query->orderBy('name')->get();
    }

    private function getObservations(): \Illuminate\Support\Collection
    {
        $query = Observation::with([
            'plant:id,name,taxon_id,site_id',
            'plant.taxon:id,binomial_name,common_name_fr',
            'plant.site:id,name',
            'phenologicalStage:id,stage_code,stage_description,main_event_description',
            'observer:id,name',
            'photos:id,observation_id,image,title,photo_type',
        ]);

        // Visibility
        if (! $this->user?->is_staff) {
            $query->where(function (Builder $q) {
                $q->where('is_public', true)
                  ->whereHas('plant', function (Builder $p) {
                      $p->where('is_private', false)
                        ->whereHas('site', fn (Builder $s) => $s->where('is_private', false));
                  });
                if ($this->user) {
                    $q->orWhere('observer_id', $this->user->id);
                }
            });
        }

        // Filters — year: skip if "all"
        if (! empty($this->filters['year']) && $this->filters['year'] !== 'all') {
            $query->whereYear('observation_date', $this->filters['year']);
        }
        if (! empty($this->filters['date_from'])) {
            $query->where('observation_date', '>=', $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $query->where('observation_date', '<=', $this->filters['date_to']);
        }
        if (! empty($this->filters['site_id'])) {
            $query->whereHas('plant', fn (Builder $p) => $p->where('site_id', $this->filters['site_id']));
        }
        if (! empty($this->filters['plant_id'])) {
            $query->where('plant_id', $this->filters['plant_id']);
        }
        if (! empty($this->filters['stage'])) {
            $query->whereHas('phenologicalStage', fn (Builder $s) => $s->where('stage_code', $this->filters['stage']));
        }
        if (! empty($this->filters['taxon'])) {
            $query->whereHas('plant', fn (Builder $p) => $p->where('taxon_id', $this->filters['taxon']));
        }
        if (! empty($this->filters['category'])) {
            $query->whereHas('plant', fn (Builder $p) => $p->where('category_id', $this->filters['category']));
        }

        return $query->orderByDesc('observation_date')->get();
    }

    private function getTaxons(\Illuminate\Support\Collection $plants): \Illuminate\Support\Collection
    {
        $taxonIds = $plants->pluck('taxon_id')->filter()->unique();

        return Taxon::whereIn('id', $taxonIds)->orderBy('binomial_name')->get();
    }

    private function getPlantActions(\Illuminate\Support\Collection $plants): \Illuminate\Support\Collection
    {
        $plantIds = $plants->pluck('id');

        $query = PlantAction::with([
            'plant:id,name,site_id',
            'plant.site:id,name',
            'actionType:id,name,slug,category,icon,color',
            'performer:id,name',
        ])->whereIn('plant_id', $plantIds);

        // Apply date filters if set
        if (! empty($this->filters['year']) && $this->filters['year'] !== 'all') {
            $query->whereYear('action_date', $this->filters['year']);
        }
        if (! empty($this->filters['date_from'])) {
            $query->where('action_date', '>=', $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $query->where('action_date', '<=', $this->filters['date_to']);
        }

        return $query->orderByDesc('action_date')->get();
    }

    // ── CSV Writers ─────────────────────────────────────────

    private function writeCsvFile(string $filename, array $headers, iterable $rows): void
    {
        $fp = fopen($this->tmpDir . '/' . $filename, 'w');
        // BOM for Excel UTF-8 compatibility
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($fp, $row, ';');
        }
        fclose($fp);
    }

    private function writeSitesCsv(\Illuminate\Support\Collection $sites): void
    {
        $headers = [
            'site_id', 'nom', 'description', 'latitude', 'longitude', 'altitude',
            'environnement', 'type_sol', 'exposition', 'pente', 'zone_climatique',
            'proprietaire', 'prive', 'plan_image', 'date_creation', 'date_modification',
        ];

        $rows = $sites->map(fn (Site $s) => [
            $s->id, $s->name, $s->description, $s->latitude, $s->longitude, $s->altitude,
            $s->environment, $s->soil_type, $s->exposure, $s->slope, $s->climate_zone,
            $s->owner?->name, $s->is_private ? 'Oui' : 'Non',
            $s->site_plan_image ? 'site_plans/' . $this->sitePlanFilename($s) : '',
            $s->created_at?->format('Y-m-d H:i'), $s->updated_at?->format('Y-m-d H:i'),
        ]);

        $this->writeCsvFile('sites.csv', $headers, $rows);
    }

    private function writePlantsCsv(\Illuminate\Support\Collection $plants): void
    {
        $headers = [
            'plante_id', 'nom', 'nom_scientifique', 'nom_commun', 'famille', 'categorie',
            'site', 'statut', 'sante', 'identification', 'date_plantation', 'hauteur',
            'cultivar', 'variete', 'cultivar_ref', 'abondance', 'abondance_initiale',
            'tags',
            'date_mort', 'cause_mort', 'nb_observations', 'nb_photos', 'nb_actions',
            'derniere_action_date', 'dernier_type_action',
            'latitude', 'longitude', 'proprietaire', 'prive',
            'photo_principale', 'notes', 'date_creation', 'date_modification',
            // Cultivation profile columns
            'culture_mois_plantation', 'culture_mois_floraison', 'culture_mois_recolte',
            'culture_exposition', 'culture_temp_min', 'culture_zone_usda',
            'culture_environnements', 'culture_sol_types', 'culture_sol_ph',
            'culture_arrosage', 'culture_difficulte', 'culture_usages',
            'culture_comestible', 'culture_toxique',
        ];

        $rows = $plants->map(function (Plant $p) {
            $lastAction = $p->actions()->with('actionType:id,name')->orderByDesc('action_date')->first();
            $cp = $p->cultivationProfile;
            $arr = fn ($v) => is_array($v) ? implode('; ', $v) : '';

            return [
            $p->id, $p->name, $p->taxon?->binomial_name, $p->taxon?->common_name_fr,
            $p->taxon?->family, $p->category?->name,
            $p->site?->name, $p->status, $p->health_status, $p->identification_certainty,
            $p->planting_date?->format('Y-m-d'), $p->height_category,
            $p->cultivar, $p->variety, $p->cultivarRef?->name,
            $p->abundance, $p->initial_abundance,
            $p->userTags->pluck('name')->implode('; '),
            $p->death_date?->format('Y-m-d'), $p->death_cause,
            $p->observations_count, $p->photos_count, $p->actions()->count(),
            $lastAction?->action_date?->format('Y-m-d'),
            $lastAction?->actionType?->name,
            $p->latitude, $p->longitude, $p->owner?->name,
            $p->is_private ? 'Oui' : 'Non',
            $p->mainPhoto ? 'images/plants/' . $this->plantPhotoFilename($p, $p->mainPhoto) : '',
            $p->notes, $p->created_at?->format('Y-m-d H:i'), $p->updated_at?->format('Y-m-d H:i'),
            // Cultivation
            $cp ? $arr($cp->planting_months) : '',
            $cp ? $arr($cp->flowering_months) : '',
            $cp ? $arr($cp->harvest_months) : '',
            $cp?->exposure ?? '',
            $cp?->hardiness_min ?? '',
            $cp?->usda_zone ?? '',
            $cp ? $arr($cp->suitable_environments) : '',
            $cp ? $arr($cp->soil_types) : '',
            $cp?->soil_ph ?? '',
            $cp?->watering_needs ?? '',
            $cp?->cultivation_difficulty ?? '',
            $cp ? $arr($cp->usage_types) : '',
            $cp ? ($cp->is_edible ? 'Oui' : 'Non') : '',
            $cp ? ($cp->is_toxic ? 'Oui' : 'Non') : '',
            ];
        });

        $this->writeCsvFile('plants.csv', $headers, $rows);
    }

    private function writeObservationsCsv(\Illuminate\Support\Collection $observations): void
    {
        $headers = [
            'observation_id', 'plante', 'plante_id', 'site', 'nom_scientifique',
            'date_observation', 'stade_phenologique', 'code_stade', 'evenement_principal',
            'intensite', 'notes', 'meteo', 'temperature', 'humidite', 'vent',
            'confiance', 'nb_photos', 'observateur', 'publique', 'validee',
            'date_creation', 'date_modification',
        ];

        $rows = $observations->map(fn (Observation $o) => [
            $o->id, $o->plant?->name, $o->plant_id, $o->plant?->site?->name,
            $o->plant?->taxon?->binomial_name,
            $o->observation_date?->format('Y-m-d'),
            $o->phenologicalStage?->stage_description, $o->phenologicalStage?->stage_code,
            $o->phenologicalStage?->main_event_description,
            $o->intensity, $o->notes, $o->weather_condition, $o->temperature,
            $o->humidity, $o->wind_speed, $o->confidence_level,
            $o->photos->count(), $o->observer?->name,
            $o->is_public ? 'Oui' : 'Non', $o->is_validated ? 'Oui' : 'Non',
            $o->created_at?->format('Y-m-d H:i'), $o->updated_at?->format('Y-m-d H:i'),
        ]);

        $this->writeCsvFile('observations.csv', $headers, $rows);
    }

    private function writeCategoriesCsv(\Illuminate\Support\Collection $categories): void
    {
        $headers = ['categorie_id', 'nom', 'type', 'icone', 'description'];

        $rows = $categories->map(fn (Category $c) => [
            $c->id, $c->name, $c->category_type, $c->icon, $c->description,
        ]);

        $this->writeCsvFile('categories.csv', $headers, $rows);
    }

    private function writeTaxonsCsv(\Illuminate\Support\Collection $taxons): void
    {
        $headers = [
            'taxon_id', 'nom_binomial', 'nom_commun_fr', 'nom_commun_it', 'nom_commun_en',
            'famille', 'genre', 'espece', 'sous_espece', 'variete', 'cultivar',
            'auteur', 'annee_publication', 'gbif_id',
        ];

        $rows = $taxons->map(fn (Taxon $t) => [
            $t->id, $t->binomial_name, $t->common_name_fr, $t->common_name_it, $t->common_name_en,
            $t->family, $t->genus, $t->species, $t->subspecies, $t->variety, $t->cultivar,
            $t->author, $t->publication_year, $t->gbif_id,
        ]);

        $this->writeCsvFile('taxons.csv', $headers, $rows);
    }

    private function writeStagesCsv(\Illuminate\Support\Collection $stages): void
    {
        $headers = ['code', 'description', 'code_evenement', 'evenement_principal', 'echelle'];

        $rows = $stages->map(fn (PhenologicalStage $s) => [
            $s->stage_code, $s->stage_description, $s->main_event_code,
            $s->main_event_description, $s->phenological_scale,
        ]);

        $this->writeCsvFile('stades_phenologiques.csv', $headers, $rows);
    }

    private function writeLayersCsv(\Illuminate\Support\Collection $layers): void
    {
        $headers = [
            'couche_id', 'site', 'site_id', 'nom_couche', 'date_debut', 'date_fin',
            'active', 'nb_plantes', 'plan_exporte', 'notes',
        ];

        $rows = $layers->map(fn (SitePlanLayer $l) => [
            $l->id, $l->site?->name, $l->site_id, $l->name,
            $l->start_date?->format('Y-m-d'), $l->end_date?->format('Y-m-d'),
            $l->is_active ? 'Oui' : 'Non',
            $l->positions->count(),
            'site_plans/' . $this->layerPlanFilename($l),
            $l->notes,
        ]);

        $this->writeCsvFile('couches.csv', $headers, $rows);
    }

    private function writeActionsCsv(\Illuminate\Support\Collection $actions): void
    {
        $headers = [
            'action_id', 'plante_id', 'plante', 'site', 'date_action',
            'type_action', 'categorie_action', 'titre', 'produit',
            'quantite', 'unite', 'dosage', 'methode', 'notes',
            'realise_par', 'cout', 'meteo',
            'date_creation', 'date_modification',
        ];

        $rows = $actions->map(fn (PlantAction $a) => [
            $a->id, $a->plant_id, $a->plant?->name, $a->plant?->site?->name,
            $a->action_date?->format('Y-m-d'),
            $a->actionType?->name, $a->actionType?->category,
            $a->title, $a->product_name,
            $a->quantity, $a->unit, $a->dosage, $a->method, $a->notes,
            $a->performer?->name ?? $a->performer_name,
            $a->cost, $a->weather_conditions,
            $a->created_at?->format('Y-m-d H:i'), $a->updated_at?->format('Y-m-d H:i'),
        ]);

        $this->writeCsvFile('actions_plantes.csv', $headers, $rows);
    }

    // ── JSON Writers ────────────────────────────────────────

    private function writeJsonFile(string $filename, mixed $data): void
    {
        file_put_contents(
            $this->tmpDir . '/json/' . $filename,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function writeSitesJson(\Illuminate\Support\Collection $sites): void
    {
        $this->writeJsonFile('sites.json', $sites->map(fn (Site $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'description' => $s->description,
            'latitude' => $s->latitude,
            'longitude' => $s->longitude,
            'altitude' => $s->altitude,
            'environment' => $s->environment,
            'soil_type' => $s->soil_type,
            'exposure' => $s->exposure,
            'slope' => $s->slope,
            'climate_zone' => $s->climate_zone,
            'is_private' => $s->is_private,
            'owner' => $s->owner?->name,
            'site_plan' => $s->site_plan_image ? 'site_plans/' . $this->sitePlanFilename($s) : null,
            'created_at' => $s->created_at?->toISOString(),
            'updated_at' => $s->updated_at?->toISOString(),
        ])->values());
    }

    private function writePlantsJson(\Illuminate\Support\Collection $plants): void
    {
        $this->writeJsonFile('plants.json', $plants->map(fn (Plant $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'taxon' => $p->taxon ? [
                'binomial_name' => $p->taxon->binomial_name,
                'common_name_fr' => $p->taxon->common_name_fr,
                'family' => $p->taxon->family,
                'genus' => $p->taxon->genus,
                'species' => $p->taxon->species,
            ] : null,
            'category' => $p->category?->name,
            'site' => $p->site?->name,
            'site_id' => $p->site_id,
            'status' => $p->status,
            'health_status' => $p->health_status,
            'identification_certainty' => $p->identification_certainty,
            'planting_date' => $p->planting_date?->format('Y-m-d'),
            'height_category' => $p->height_category,
            'cultivar' => $p->cultivar,
            'variety' => $p->variety,
            'cultivar_ref' => $p->cultivarRef?->name,
            'abundance' => $p->abundance,
            'initial_abundance' => $p->initial_abundance,
            'tags' => $p->userTags->map(fn ($t) => ['name' => $t->name, 'color' => $t->color])->values(),
            'death_date' => $p->death_date?->format('Y-m-d'),
            'death_cause' => $p->death_cause,
            'latitude' => $p->latitude,
            'longitude' => $p->longitude,
            'observations_count' => $p->observations_count,
            'photos_count' => $p->photos_count,
            'is_private' => $p->is_private,
            'owner' => $p->owner?->name,
            'photos' => $p->photos->map(fn ($ph) => [
                'id' => $ph->id,
                'path' => 'images/plants/' . $this->plantPhotoFilename($p, $ph),
                'title' => $ph->title,
                'type' => $ph->photo_type,
                'is_main' => $ph->is_main_photo,
            ])->values(),
            'notes' => $p->notes,
            'cultivation_profile' => $p->cultivationProfile ? [
                'planting_months' => $p->cultivationProfile->planting_months,
                'sowing_months' => $p->cultivationProfile->sowing_months,
                'harvest_months' => $p->cultivationProfile->harvest_months,
                'flowering_months' => $p->cultivationProfile->flowering_months,
                'exposure' => $p->cultivationProfile->exposure,
                'hardiness_min' => $p->cultivationProfile->hardiness_min,
                'usda_zone' => $p->cultivationProfile->usda_zone,
                'suitable_environments' => $p->cultivationProfile->suitable_environments,
                'soil_types' => $p->cultivationProfile->soil_types,
                'soil_ph' => $p->cultivationProfile->soil_ph,
                'soil_drainage' => $p->cultivationProfile->soil_drainage,
                'soil_fertility' => $p->cultivationProfile->soil_fertility,
                'mature_height_min' => $p->cultivationProfile->mature_height_min,
                'mature_height_max' => $p->cultivationProfile->mature_height_max,
                'mature_spread_min' => $p->cultivationProfile->mature_spread_min,
                'mature_spread_max' => $p->cultivationProfile->mature_spread_max,
                'watering_needs' => $p->cultivationProfile->watering_needs,
                'watering_notes' => $p->cultivationProfile->watering_notes,
                'fertilizing_frequency' => $p->cultivationProfile->fertilizing_frequency,
                'fertilizing_notes' => $p->cultivationProfile->fertilizing_notes,
                'pruning_period' => $p->cultivationProfile->pruning_period,
                'pruning_notes' => $p->cultivationProfile->pruning_notes,
                'mulching' => $p->cultivationProfile->mulching,
                'winter_protection' => $p->cultivationProfile->winter_protection,
                'pest_susceptibility' => $p->cultivationProfile->pest_susceptibility,
                'disease_susceptibility' => $p->cultivationProfile->disease_susceptibility,
                'companion_plants' => $p->cultivationProfile->companion_plants,
                'avoid_near' => $p->cultivationProfile->avoid_near,
                'propagation_methods' => $p->cultivationProfile->propagation_methods,
                'cultivation_difficulty' => $p->cultivationProfile->cultivation_difficulty,
                'usage_types' => $p->cultivationProfile->usage_types,
                'is_edible' => $p->cultivationProfile->is_edible,
                'is_toxic' => $p->cultivationProfile->is_toxic,
                'notes' => $p->cultivationProfile->notes,
                'source' => $p->cultivationProfile->source,
                'extra' => $p->cultivationProfile->extra,
            ] : null,
            'created_at' => $p->created_at?->toISOString(),
            'updated_at' => $p->updated_at?->toISOString(),
        ])->values());
    }

    private function writeObservationsJson(\Illuminate\Support\Collection $observations): void
    {
        $this->writeJsonFile('observations.json', $observations->map(fn (Observation $o) => [
            'id' => $o->id,
            'plant' => $o->plant?->name,
            'plant_id' => $o->plant_id,
            'site' => $o->plant?->site?->name,
            'taxon' => $o->plant?->taxon?->binomial_name,
            'observation_date' => $o->observation_date?->format('Y-m-d'),
            'phenological_stage' => $o->phenologicalStage ? [
                'code' => $o->phenologicalStage->stage_code,
                'description' => $o->phenologicalStage->stage_description,
                'main_event' => $o->phenologicalStage->main_event_description,
            ] : null,
            'intensity' => $o->intensity,
            'notes' => $o->notes,
            'weather' => $o->weather_condition,
            'temperature' => $o->temperature,
            'humidity' => $o->humidity,
            'wind_speed' => $o->wind_speed,
            'confidence_level' => $o->confidence_level,
            'observer' => $o->observer?->name,
            'is_public' => $o->is_public,
            'is_validated' => $o->is_validated,
            'photos' => $o->photos->map(fn ($ph) => [
                'id' => $ph->id,
                'path' => 'images/observations/' . $this->obsPhotoFilename($o, $ph),
                'title' => $ph->title,
                'type' => $ph->photo_type,
            ])->values(),
            'created_at' => $o->created_at?->toISOString(),
            'updated_at' => $o->updated_at?->toISOString(),
        ])->values());
    }

    private function writeCategoriesJson(\Illuminate\Support\Collection $categories): void
    {
        $this->writeJsonFile('categories.json', $categories->map(fn (Category $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->category_type,
            'icon' => $c->icon,
            'description' => $c->description,
        ])->values());
    }

    private function writeTaxonsJson(\Illuminate\Support\Collection $taxons): void
    {
        $this->writeJsonFile('taxons.json', $taxons->toArray());
    }

    private function writeStagesJson(\Illuminate\Support\Collection $stages): void
    {
        $this->writeJsonFile('stades_phenologiques.json', $stages->toArray());
    }

    private function writeLayersJson(\Illuminate\Support\Collection $layers): void
    {
        $this->writeJsonFile('couches.json', $layers->map(fn (SitePlanLayer $l) => [
            'id' => $l->id,
            'site' => $l->site?->name,
            'site_id' => $l->site_id,
            'name' => $l->name,
            'start_date' => $l->start_date?->format('Y-m-d'),
            'end_date' => $l->end_date?->format('Y-m-d'),
            'is_active' => $l->is_active,
            'drawing_overlay' => $l->drawing_overlay,
            'plan_file' => 'site_plans/' . $this->layerPlanFilename($l),
            'plants' => $l->positions->map(fn (PlantLayerPosition $pos) => [
                'plant_id' => $pos->plant_id,
                'plant_name' => $pos->plant?->name,
                'map_position_x' => $pos->map_position_x,
                'map_position_y' => $pos->map_position_y,
                'notes' => $pos->notes,
            ])->values(),
            'notes' => $l->notes,
        ])->values());
    }

    private function writeActionsJson(\Illuminate\Support\Collection $actions): void
    {
        $this->writeJsonFile('actions_plantes.json', $actions->map(fn (PlantAction $a) => [
            'id' => $a->id,
            'plant_id' => $a->plant_id,
            'plant_name' => $a->plant?->name,
            'site' => $a->plant?->site?->name,
            'action_date' => $a->action_date?->format('Y-m-d'),
            'action_type' => $a->actionType?->name,
            'action_category' => $a->actionType?->category,
            'title' => $a->title,
            'product_name' => $a->product_name,
            'quantity' => $a->quantity,
            'unit' => $a->unit,
            'dosage' => $a->dosage,
            'method' => $a->method,
            'notes' => $a->notes,
            'performed_by' => $a->performer?->name ?? $a->performer_name,
            'cost' => $a->cost,
            'weather_conditions' => $a->weather_conditions,
            'created_at' => $a->created_at?->toISOString(),
            'updated_at' => $a->updated_at?->toISOString(),
        ])->values());
    }

    // ── Image export ────────────────────────────────────────

    private function resolveImagePath(string $relativePath): ?string
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($relativePath)) {
                return Storage::disk($disk)->path($relativePath);
            }
        }

        return null;
    }

    private function exportPlantPhotos(\Illuminate\Support\Collection $plants): void
    {
        $count = 0;
        foreach ($plants as $plant) {
            foreach ($plant->photos as $photo) {
                if (! $photo->image) {
                    continue;
                }
                $srcPath = $this->resolveImagePath($photo->image);
                if ($srcPath && file_exists($srcPath)) {
                    $dest = $this->tmpDir . '/images/plants/' . $this->plantPhotoFilename($plant, $photo);
                    copy($srcPath, $dest);
                    $count++;
                }
            }
        }
        $this->counts['plant_photos'] = $count;
    }

    private function exportObservationPhotos(\Illuminate\Support\Collection $observations): void
    {
        $count = 0;
        foreach ($observations as $obs) {
            foreach ($obs->photos as $photo) {
                if (! $photo->image) {
                    continue;
                }
                $srcPath = $this->resolveImagePath($photo->image);
                if ($srcPath && file_exists($srcPath)) {
                    $dest = $this->tmpDir . '/images/observations/' . $this->obsPhotoFilename($obs, $photo);
                    copy($srcPath, $dest);
                    $count++;
                }
            }
        }
        $this->counts['observation_photos'] = $count;
    }

    private function exportSitePlans(\Illuminate\Support\Collection $sites): void
    {
        $count = 0;
        foreach ($sites as $site) {
            if (! $site->site_plan_image) {
                continue;
            }
            $srcPath = $this->resolveImagePath($site->site_plan_image);
            if ($srcPath && file_exists($srcPath)) {
                $dest = $this->tmpDir . '/site_plans/' . $this->sitePlanFilename($site);
                copy($srcPath, $dest);
                $count++;
            }
        }
        $this->counts['site_plans'] = $count;
    }

    private function exportLayerPlans(\Illuminate\Support\Collection $layers): void
    {
        $svgW = 800;
        $svgH = 600;

        foreach ($layers as $layer) {
            $shapes = is_array($layer->drawing_overlay) ? $layer->drawing_overlay : [];
            $positions = $layer->positions;

            // Skip layers with no content
            if (empty($shapes) && $positions->isEmpty()) {
                continue;
            }

            $svgContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $svgContent .= '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"';
            $svgContent .= " viewBox=\"0 0 {$svgW} {$svgH}\" width=\"{$svgW}\" height=\"{$svgH}\">\n";

            // Title and metadata
            $siteName = htmlspecialchars($layer->site?->name ?? '', ENT_XML1);
            $layerName = htmlspecialchars($layer->name ?? '', ENT_XML1);
            $dateFrom = $layer->start_date?->format('Y-m-d') ?? '';
            $dateTo = $layer->end_date?->format('Y-m-d') ?? 'en cours';
            $svgContent .= "  <title>Couche: {$layerName} — Site: {$siteName} ({$dateFrom} / {$dateTo})</title>\n";

            // Background: embed site plan image if available
            $site = $layer->site;
            if ($site && $site->site_plan_image) {
                $imgPath = $this->resolveImagePath($site->site_plan_image);
                if ($imgPath && file_exists($imgPath)) {
                    $mime = mime_content_type($imgPath) ?: 'image/png';
                    $base64 = base64_encode(file_get_contents($imgPath));
                    $svgContent .= "  <image href=\"data:{$mime};base64,{$base64}\" x=\"0\" y=\"0\" width=\"{$svgW}\" height=\"{$svgH}\" preserveAspectRatio=\"none\" />\n";
                }
            } else {
                // Grid background
                $svgContent .= "  <defs>\n";
                $svgContent .= "    <pattern id=\"grid\" width=\"20\" height=\"20\" patternUnits=\"userSpaceOnUse\">\n";
                $svgContent .= "      <path d=\"M 20 0 L 0 0 0 20\" fill=\"none\" stroke=\"#dee2e6\" stroke-width=\"0.5\"/>\n";
                $svgContent .= "    </pattern>\n";
                $svgContent .= "  </defs>\n";
                $svgContent .= "  <rect width=\"100%\" height=\"100%\" fill=\"url(#grid)\" />\n";
            }

            // Drawing shapes
            if (! empty($shapes)) {
                $svgContent .= "  <g id=\"drawings\">\n";
                foreach ($shapes as $shape) {
                    $type = $shape['type'] ?? '';
                    switch ($type) {
                        case 'rect':
                            $x = $shape['x'] ?? 0;
                            $y = $shape['y'] ?? 0;
                            $w = $shape['width'] ?? 0;
                            $h = $shape['height'] ?? 0;
                            $stroke = htmlspecialchars($shape['stroke'] ?? '#000', ENT_XML1);
                            $sw = $shape['strokeWidth'] ?? 2;
                            $fill = htmlspecialchars($shape['fill'] ?? 'none', ENT_XML1);
                            $svgContent .= "    <rect x=\"{$x}\" y=\"{$y}\" width=\"{$w}\" height=\"{$h}\" stroke=\"{$stroke}\" stroke-width=\"{$sw}\" fill=\"{$fill}\" />\n";
                            break;

                        case 'circle':
                            $cx = $shape['cx'] ?? 0;
                            $cy = $shape['cy'] ?? 0;
                            $r = $shape['r'] ?? 0;
                            $stroke = htmlspecialchars($shape['stroke'] ?? '#000', ENT_XML1);
                            $sw = $shape['strokeWidth'] ?? 2;
                            $fill = htmlspecialchars($shape['fill'] ?? 'none', ENT_XML1);
                            $svgContent .= "    <circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" stroke=\"{$stroke}\" stroke-width=\"{$sw}\" fill=\"{$fill}\" />\n";
                            break;

                        case 'polyline':
                            $points = htmlspecialchars($shape['points'] ?? '', ENT_XML1);
                            $stroke = htmlspecialchars($shape['stroke'] ?? '#000', ENT_XML1);
                            $sw = $shape['strokeWidth'] ?? 2;
                            $fill = htmlspecialchars($shape['fill'] ?? 'none', ENT_XML1);
                            $svgContent .= "    <polyline points=\"{$points}\" stroke=\"{$stroke}\" stroke-width=\"{$sw}\" fill=\"{$fill}\" />\n";
                            break;

                        case 'text':
                            $x = $shape['x'] ?? 0;
                            $y = $shape['y'] ?? 0;
                            $fontSize = $shape['fontSize'] ?? 16;
                            $fill = htmlspecialchars($shape['fill'] ?? '#000', ENT_XML1);
                            $content = htmlspecialchars($shape['content'] ?? '', ENT_XML1);
                            $svgContent .= "    <text x=\"{$x}\" y=\"{$y}\" font-size=\"{$fontSize}\" fill=\"{$fill}\" font-family=\"sans-serif\">{$content}</text>\n";
                            break;
                    }
                }
                $svgContent .= "  </g>\n";
            }

            // Plant position markers
            if ($positions->isNotEmpty()) {
                $svgContent .= "  <g id=\"plants\">\n";
                foreach ($positions as $pos) {
                    $px = ($pos->map_position_x / 100) * $svgW;
                    $py = ($pos->map_position_y / 100) * $svgH;
                    $plantName = htmlspecialchars($pos->plant?->name ?? "Plante #{$pos->plant_id}", ENT_XML1);

                    // Marker circle
                    $svgContent .= "    <circle cx=\"{$px}\" cy=\"{$py}\" r=\"6\" fill=\"#28a745\" stroke=\"#fff\" stroke-width=\"1.5\" />\n";
                    // Label
                    $labelX = $px + 10;
                    $labelY = $py + 4;
                    $svgContent .= "    <text x=\"{$labelX}\" y=\"{$labelY}\" font-size=\"10\" fill=\"#333\" font-family=\"sans-serif\">{$plantName}</text>\n";
                }
                $svgContent .= "  </g>\n";
            }

            $svgContent .= "</svg>\n";

            $dest = $this->tmpDir . '/site_plans/' . $this->layerPlanFilename($layer);
            file_put_contents($dest, $svgContent);
        }
    }

    // ── Database backup (MySQL-compatible SQL) ──────────────

    private function exportDatabase(): void
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");
        $sqlPath = $this->tmpDir . '/database/phenolab_backup.sql';

        if ($connection === 'mysql') {
            // Native mysqldump
            $cmd = sprintf(
                'mysqldump --single-transaction --routines --triggers -h %s -P %s -u %s -p%s %s > %s 2>/dev/null',
                escapeshellarg($dbConfig['host'] ?? '127.0.0.1'),
                escapeshellarg($dbConfig['port'] ?? '3306'),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($sqlPath)
            );
            exec($cmd);
        } elseif ($connection === 'pgsql') {
            $cmd = sprintf(
                'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s > %s 2>/dev/null',
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host'] ?? '127.0.0.1'),
                escapeshellarg($dbConfig['port'] ?? '5432'),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($sqlPath)
            );
            exec($cmd);
        } elseif ($connection === 'sqlite' && file_exists($dbConfig['database'])) {
            // Copy raw SQLite file
            copy($dbConfig['database'], $this->tmpDir . '/database/phenolab_backup.sqlite');

            // Also generate MySQL-compatible SQL dump
            $this->sqliteToMysqlDump($dbConfig['database'], $sqlPath);
        }
    }

    /**
     * Convert SQLite database to a MySQL-compatible SQL dump.
     */
    private function sqliteToMysqlDump(string $sqliteDbPath, string $outputPath): void
    {
        $fp = fopen($outputPath, 'w');

        fwrite($fp, "-- PhenoLab Database Backup\n");
        fwrite($fp, "-- Source: SQLite (converted to MySQL-compatible SQL)\n");
        fwrite($fp, '-- Date: ' . now()->format('Y-m-d H:i:s') . "\n");
        fwrite($fp, "-- ============================================================\n\n");
        fwrite($fp, "SET NAMES utf8mb4;\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        $pdo = new \PDO("sqlite:{$sqliteDbPath}");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Get all table names (skip sqlite internals)
        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        )->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Get CREATE TABLE from sqlite
            $createStmt = $pdo->query(
                "SELECT sql FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($table)
            )->fetchColumn();

            if (! $createStmt) {
                continue;
            }

            // Convert SQLite CREATE TABLE to MySQL syntax
            $mysqlCreate = $this->convertCreateTable($createStmt, $table);
            fwrite($fp, "-- Table: {$table}\n");
            fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fp, $mysqlCreate . ";\n\n");

            // Export data
            $rows = $pdo->query("SELECT * FROM \"{$table}\"");
            $columns = null;
            $batch = [];
            $batchSize = 100;

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                if ($columns === null) {
                    $columns = array_keys($row);
                    $colList = implode('`, `', $columns);
                }

                $values = array_map(function ($v) use ($pdo) {
                    if ($v === null) {
                        return 'NULL';
                    }
                    if (is_int($v) || is_float($v)) {
                        return (string) $v;
                    }

                    return $pdo->quote($v);
                }, array_values($row));

                $batch[] = '(' . implode(', ', $values) . ')';

                if (count($batch) >= $batchSize) {
                    fwrite($fp, "INSERT INTO `{$table}` (`{$colList}`) VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }

            if (! empty($batch) && $columns !== null) {
                $colList = implode('`, `', $columns);
                fwrite($fp, "INSERT INTO `{$table}` (`{$colList}`) VALUES\n" . implode(",\n", $batch) . ";\n");
            }

            fwrite($fp, "\n");
        }

        fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($fp);
    }

    /**
     * Convert a SQLite CREATE TABLE statement to MySQL syntax.
     */
    private function convertCreateTable(string $sql, string $table): string
    {
        // Wrap table name in backticks
        $sql = preg_replace('/CREATE TABLE\s+"?(\w+)"?/i', "CREATE TABLE `{$table}`", $sql);

        // Replace AUTOINCREMENT with AUTO_INCREMENT
        $sql = str_ireplace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);

        // Replace "text" type columns (SQLite style)
        // Keep varchar/text as-is but ensure they have charset
        $sql = preg_replace('/\binteger\b/i', 'BIGINT', $sql);

        // Remove SQLite-specific clauses
        $sql = preg_replace('/\bIF NOT EXISTS\b/i', '', $sql);

        // Add ENGINE and CHARSET
        $sql = rtrim($sql, ';');
        $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        return $sql;
    }

    // ── Metadata ────────────────────────────────────────────

    private function writeManifest(): void
    {
        $yearLabel = (! empty($this->filters['year']) && $this->filters['year'] !== 'all')
            ? $this->filters['year']
            : 'Toutes les annees';

        $manifest = [
            'export_version' => '1.1',
            'application' => 'PhenoLab',
            'export_timestamp' => now()->toISOString(),
            'export_user' => $this->user?->name ?? 'Anonyme',
            'filters_applied' => array_merge($this->filters, ['year_label' => $yearLabel]),
            'counts' => $this->counts,
            'database_connection' => config('database.default'),
            'includes_mysql_dump' => true,
        ];

        file_put_contents(
            $this->tmpDir . '/metadata/export_manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function writeReadme(string $format): void
    {
        $date = now()->format('d/m/Y H:i');
        $user = $this->user?->name ?? 'Anonyme';
        $yearLabel = (! empty($this->filters['year']) && $this->filters['year'] !== 'all')
            ? $this->filters['year']
            : 'Toutes les annees';

        $content = <<<README
        ============================================================
        PhenoLab — Export de donnees
        ============================================================
        Date d'export : {$date}
        Utilisateur   : {$user}
        Format        : {$format}
        Annee         : {$yearLabel}

        CONTENU DE L'ARCHIVE
        --------------------

        Fichiers CSV (separateur: point-virgule, encodage: UTF-8 BOM)
        ---------------------------------------------------------------
        sites.csv                   Sites d'observation
        plants.csv                  Plantes enregistrees
        observations.csv            Observations phenologiques
        categories.csv              Categories de plantes
        taxons.csv                  Referentiel taxonomique
        stades_phenologiques.csv    Stades phenologiques BBCH
        actions_plantes.csv         Actions / interventions sur les plantes

        -> Ouvrir dans Excel / LibreOffice :
           double-cliquez sur le fichier CSV, selectionnez
           "point-virgule" comme separateur.

        Fichiers JSON (dossier json/)
        ---------------------------------------------------------------
        json/sites.json             Donnees structurees des sites
        json/plants.json            Plantes avec photos liees
        json/observations.json      Observations avec photos liees
        json/categories.json        Categories
        json/taxons.json            Taxons
        json/stades_phenologiques.json  Stades phenologiques
        json/actions_plantes.json   Actions / interventions sur les plantes

        -> Format reutilisable pour import API ou traitement automatise.

        Images (dossier images/)
        ---------------------------------------------------------------
        images/plants/              Photos des plantes
          Nommage: plante_<nom>_site_<site>_photo_<id>[_principale].<ext>
        images/observations/        Photos des observations
          Nommage: obs_<date>_<plante>_<site>_photo_<id>.<ext>

        Plans de sites (dossier site_plans/)
        ---------------------------------------------------------------
        site_plans/                 Images des plans de sites
          Nommage: plan_<nom-site>_site_<id>.<ext>

        Couches de sites (dossier site_plans/)
        ---------------------------------------------------------------
        couches.csv                 Liste des couches avec dates et nb plantes
        json/couches.json           Couches avec dessins et positions (JSON)
        site_plans/couche_*.svg     Plans SVG de chaque couche avec dessins
                                    et marqueurs de plantes
          Nommage: couche_<site>_<debut>_a_<fin>_<nom-couche>_id<id>.svg

        Base de donnees (dossier database/)
        ---------------------------------------------------------------
        database/phenolab_backup.sql      Dump SQL compatible MySQL
        database/phenolab_backup.sqlite   Copie brute SQLite (si applicable)

        -> Le fichier .sql peut etre importe directement dans MySQL/MariaDB :
           mysql -u utilisateur -p base_de_donnees < phenolab_backup.sql

        Metadonnees (dossier metadata/)
        ---------------------------------------------------------------
        metadata/export_manifest.json   Informations sur l'export
                                        (date, filtres, compteurs)

        STATISTIQUES
        ------------
        Sites          : {$this->counts['sites']}
        Plantes        : {$this->counts['plants']}
        Observations   : {$this->counts['observations']}
        Photos plantes : {$this->counts['plant_photos']}
        Photos obs.    : {$this->counts['observation_photos']}
        Plans de sites : {$this->counts['site_plans']}
        Couches        : {$this->counts['layers']}
        Actions        : {$this->counts['plant_actions']}
        Categories     : {$this->counts['categories']}
        Taxons         : {$this->counts['taxons']}
        Stades         : {$this->counts['stages']}

        ============================================================
        Genere par PhenoLab v1.0
        ============================================================
        README;

        file_put_contents($this->tmpDir . '/README.txt', $content);
    }

    // ── ZIP builder ─────────────────────────────────────────

    private function buildZip(string $zipPath): void
    {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->addDirectoryToZip($zip, $this->tmpDir, '');

        $zip->close();
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $prefix): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($dir) + 1);
            if ($prefix) {
                $relativePath = $prefix . '/' . $relativePath;
            }
            $zip->addFile($filePath, $relativePath);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }

        rmdir($dir);
    }
}
