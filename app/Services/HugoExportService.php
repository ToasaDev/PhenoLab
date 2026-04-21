<?php

namespace App\Services;

use App\Models\Observation;
use App\Models\Plant;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class HugoExportService
{
    private string $tmpDir;

    // ── Helpers ──────────────────────────────────────────────

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

    private function resolveImagePath(string $relativePath): ?string
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($relativePath)) {
                return Storage::disk($disk)->path($relativePath);
            }
        }

        return null;
    }

    private function yamlEscape(?string $value): string
    {
        if ($value === null || $value === '') {
            return '""';
        }

        // Wrap in quotes if contains special YAML chars
        if (preg_match('/[:#\[\]{}&*!|>\'"%@`,\n]/', $value)) {
            return '"' . str_replace('"', '\\"', str_replace('\\', '\\\\', $value)) . '"';
        }

        return $value;
    }

    // ── Main export ─────────────────────────────────────────

    public function export(): string
    {
        $this->tmpDir = sys_get_temp_dir() . '/phenolab_hugo_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        // Hugo structure
        mkdir($this->tmpDir . '/content/sites', 0755, true);
        mkdir($this->tmpDir . '/content/plantes', 0755, true);
        mkdir($this->tmpDir . '/content/observations', 0755, true);
        mkdir($this->tmpDir . '/static/images/plants', 0755, true);
        mkdir($this->tmpDir . '/static/images/observations', 0755, true);
        mkdir($this->tmpDir . '/layouts/_default', 0755, true);
        mkdir($this->tmpDir . '/layouts/plantes', 0755, true);
        mkdir($this->tmpDir . '/themes', 0755, true);
        file_put_contents($this->tmpDir . '/themes/.gitkeep', '');

        // Collect data (superadmin = all data, no visibility filter)
        $sites = Site::with('owner:id,name')->orderBy('name')->get();
        $plants = Plant::with([
            'taxon:id,binomial_name,common_name_fr,family,genus,species',
            'category:id,name,icon,category_type',
            'site:id,name',
            'owner:id,name',
            'photos:id,plant_id,image,title,photo_type,is_main_photo',
            'observations' => fn ($q) => $q->with([
                'phenologicalStage:id,stage_code,stage_description',
                'observer:id,name',
                'photos:id,observation_id,image,title',
            ])->orderByDesc('observation_date'),
            'cultivationProfile',
            'cultivarRef:id,name,upov_code',
            'userTags:user_plant_tags.id,name,color',
        ])->withCount('observations', 'photos')->orderBy('name')->get();

        $observations = Observation::with([
            'plant:id,name,taxon_id,site_id',
            'plant.taxon:id,binomial_name,common_name_fr',
            'plant.site:id,name',
            'phenologicalStage:id,stage_code,stage_description,main_event_description',
            'observer:id,name',
            'photos:id,observation_id,image,title',
        ])->orderByDesc('observation_date')->get();

        // Generate files
        $this->writeHugoConfig($sites->count(), $plants->count(), $observations->count());
        $this->writeLayouts();
        $this->writeSitesContent($sites);
        $this->writePlantsContent($plants);
        $this->writeObservationsContent($observations);
        $this->writeHomePage($sites->count(), $plants->count(), $observations->count());

        // Copy photos
        $this->copyPlantPhotos($plants);
        $this->copyObservationPhotos($observations);

        // Build ZIP
        $zipPath = sys_get_temp_dir() . '/phenolab_hugo_' . now()->format('Y-m-d_H-i') . '.zip';
        $this->buildZip($zipPath);

        // Cleanup
        $this->deleteDirectory($this->tmpDir);

        return $zipPath;
    }

    // ── Hugo config ─────────────────────────────────────────

    private function writeHugoConfig(int $siteCount, int $plantCount, int $obsCount): void
    {
        $date = now()->format('d/m/Y H:i');
        $config = <<<TOML
baseURL = "/"
languageCode = "fr"
title = "PhenoLab - Archive botanique"

[params]
  description = "Archive statique des donnees PhenoLab - generee le {$date}"
  sites = {$siteCount}
  plantes = {$plantCount}
  observations = {$obsCount}
  generated = "{$date}"

[markup.goldmark.renderer]
  unsafe = true

[taxonomies]
  site = "sites"
  categorie = "categories"
TOML;

        file_put_contents($this->tmpDir . '/hugo.toml', $config);
    }

    // ── Layouts ─────────────────────────────────────────────

    private function writeLayouts(): void
    {
        // baseof
        $baseof = <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ .Title }} | PhenoLab</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; background: #f8f9fa; }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
        header { background: linear-gradient(135deg, #2d6a4f, #40916c); color: white; padding: 20px 0; margin-bottom: 30px; }
        header h1 { font-size: 1.5rem; }
        header .subtitle { opacity: 0.85; font-size: 0.9rem; }
        nav { margin-top: 10px; }
        nav a { color: white; text-decoration: none; margin-right: 18px; opacity: 0.9; font-size: 0.95rem; }
        nav a:hover { opacity: 1; text-decoration: underline; }
        main { min-height: 60vh; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .badge-green { background: #d4edda; color: #155724; }
        .badge-blue { background: #d1ecf1; color: #0c5460; }
        .badge-orange { background: #fff3cd; color: #856404; }
        .badge-red { background: #f8d7da; color: #721c24; }
        .badge-gray { background: #e9ecef; color: #495057; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e9ecef; }
        th { background: #f1f3f5; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: #6c757d; }
        tr:hover { background: #f8f9fa; }
        a { color: #2d6a4f; }
        .stats { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 25px; }
        .stat { background: white; border-radius: 8px; padding: 15px 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
        .stat .num { font-size: 2rem; font-weight: 700; color: #2d6a4f; }
        .stat .label { font-size: 0.85rem; color: #6c757d; }
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; margin: 15px 0; }
        .photo-grid img { width: 100%; height: 160px; object-fit: cover; border-radius: 6px; }
        .plant-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; margin: 15px 0; }
        .plant-meta dt { font-weight: 600; color: #6c757d; font-size: 0.85rem; }
        .plant-meta dd { margin-bottom: 8px; }
        footer { text-align: center; padding: 30px 0; color: #adb5bd; font-size: 0.85rem; margin-top: 40px; border-top: 1px solid #e9ecef; }
        @media (max-width: 600px) { .plant-meta { grid-template-columns: 1fr; } .stats { flex-direction: column; } }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>PhenoLab - Archive botanique</h1>
            <div class="subtitle">{{ .Site.Params.description }}</div>
            <nav>
                <a href="/">Accueil</a>
                <a href="/sites/">Sites</a>
                <a href="/plantes/">Plantes</a>
                <a href="/observations/">Observations</a>
            </nav>
        </div>
    </header>
    <main class="container">
        {{ block "main" . }}{{ end }}
    </main>
    <footer>
        <div class="container">
            PhenoLab &mdash; Archive generee le {{ .Site.Params.generated }}
        </div>
    </footer>
</body>
</html>
HTML;

        file_put_contents($this->tmpDir . '/layouts/_default/baseof.html', $baseof);

        // List layout
        $list = <<<'HTML'
{{ define "main" }}
<h2>{{ .Title }}</h2>
{{ .Content }}
{{ end }}
HTML;

        file_put_contents($this->tmpDir . '/layouts/_default/list.html', $list);

        // Single layout (default)
        $single = <<<'HTML'
{{ define "main" }}
<article class="card">
    <h2>{{ .Title }}</h2>
    {{ .Content }}
</article>
{{ end }}
HTML;

        file_put_contents($this->tmpDir . '/layouts/_default/single.html', $single);

        // Plant single layout
        $plantSingle = <<<'HTML'
{{ define "main" }}
<article>
    <div class="card">
        <h2>{{ .Title }}
            {{ with .Params.identification_certainty }}
                {{ if eq . "certain" }}<span class="badge badge-green">Certaine</span>
                {{ else if eq . "uncertain" }}<span class="badge badge-orange">Douteuse</span>
                {{ else if eq . "undetermined" }}<span class="badge badge-red">A determiner</span>
                {{ end }}
            {{ end }}
            {{ with .Params.status }}
                {{ if eq . "alive" }}<span class="badge badge-green">Vivant</span>
                {{ else if eq . "dead" }}<span class="badge badge-red">Mort</span>
                {{ else }}<span class="badge badge-gray">{{ . }}</span>
                {{ end }}
            {{ end }}
        </h2>

        <dl class="plant-meta">
            {{ with .Params.taxon }}<dt>Taxon</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.common_name }}<dt>Nom commun</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.family }}<dt>Famille</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.genus }}<dt>Genre</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.species }}<dt>Espece</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.site_name }}<dt>Site</dt><dd><a href="/sites/{{ $.Params.site_slug | default "" }}/">{{ . }}</a></dd>{{ end }}
            {{ with .Params.category }}<dt>Categorie</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.cultivar }}<dt>Cultivar</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.variety }}<dt>Variete</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.cultivar_ref }}<dt>Ref. cultivar</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.planting_date }}<dt>Date de plantation</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.height_category }}<dt>Hauteur</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.health_status }}<dt>Etat sanitaire</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.abundance }}<dt>Abondance</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.owner }}<dt>Proprietaire</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.tags }}<dt>Tags</dt><dd>{{ delimit . ", " }}</dd>{{ end }}
        </dl>

        {{ with .Params.notes }}<h4>Notes</h4><p>{{ . }}</p>{{ end }}
        {{ with .Params.care_notes }}<h4>Notes de soin</h4><p>{{ . }}</p>{{ end }}
        {{ with .Params.ecological_notes }}<h4>Notes ecologiques</h4><p>{{ . }}</p>{{ end }}
        {{ with .Params.anecdotes }}<h4>Anecdotes</h4><p>{{ . }}</p>{{ end }}
        {{ with .Params.cultural_significance }}<h4>Importance culturelle</h4><p>{{ . }}</p>{{ end }}
    </div>

    {{ if .Params.photos }}
    <div class="card">
        <h3>Photos ({{ len .Params.photos }})</h3>
        <div class="photo-grid">
            {{ range .Params.photos }}
                <img src="{{ . }}" alt="Photo" loading="lazy">
            {{ end }}
        </div>
    </div>
    {{ end }}

    {{ if .Params.observations_data }}
    <div class="card">
        <h3>Observations ({{ len .Params.observations_data }})</h3>
        <table>
            <thead>
                <tr><th>Date</th><th>Stade</th><th>Observateur</th><th>Notes</th></tr>
            </thead>
            <tbody>
                {{ range .Params.observations_data }}
                <tr>
                    <td>{{ .date }}</td>
                    <td><span class="badge badge-blue">{{ .stage }}</span></td>
                    <td>{{ .observer }}</td>
                    <td>{{ .notes }}</td>
                </tr>
                {{ end }}
            </tbody>
        </table>
    </div>
    {{ end }}

    {{ if .Params.cultivation }}
    <div class="card">
        <h3>Profil de culture</h3>
        <dl class="plant-meta">
            {{ with .Params.cultivation.soil_type }}<dt>Type de sol</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.cultivation.sun_exposure }}<dt>Exposition</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.cultivation.watering }}<dt>Arrosage</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.cultivation.hardiness_zone }}<dt>Zone rusticite</dt><dd>{{ . }}</dd>{{ end }}
            {{ with .Params.cultivation.notes }}<dt>Notes</dt><dd>{{ . }}</dd>{{ end }}
        </dl>
    </div>
    {{ end }}
</article>
{{ end }}
HTML;

        file_put_contents($this->tmpDir . '/layouts/plantes/single.html', $plantSingle);
    }

    // ── Content pages ───────────────────────────────────────

    private function writeHomePage(int $siteCount, int $plantCount, int $obsCount): void
    {
        $date = now()->format('d/m/Y a H:i');
        $content = <<<MD
---
title: "Accueil"
---

<div class="stats">
    <div class="stat"><div class="num">{$siteCount}</div><div class="label">Sites</div></div>
    <div class="stat"><div class="num">{$plantCount}</div><div class="label">Plantes</div></div>
    <div class="stat"><div class="num">{$obsCount}</div><div class="label">Observations</div></div>
</div>

Cette archive statique a ete generee le **{$date}** a partir de l'application PhenoLab.
Elle contient l'ensemble des donnees de la base : sites, plantes et observations avec leurs photos.

Pour consulter le site, installez [Hugo](https://gohugo.io/) puis lancez :

```bash
hugo server
```
MD;

        file_put_contents($this->tmpDir . '/content/_index.md', $content);
    }

    private function writeSitesContent(\Illuminate\Support\Collection $sites): void
    {
        // Sites list page
        $rows = '';
        foreach ($sites as $site) {
            $name = e($site->name);
            $slug = $this->sanitize($site->name) . '-' . $site->id;
            $owner = e($site->owner?->name ?? '-');
            $lat = $site->latitude ?? '-';
            $lng = $site->longitude ?? '-';
            $rows .= "<tr><td><a href=\"/sites/{$slug}/\">{$name}</a></td><td>{$owner}</td><td>{$lat}, {$lng}</td></tr>\n";
        }

        $index = <<<MD
---
title: "Sites"
---

<table>
<thead><tr><th>Nom</th><th>Proprietaire</th><th>Coordonnees</th></tr></thead>
<tbody>
{$rows}
</tbody>
</table>
MD;

        file_put_contents($this->tmpDir . '/content/sites/_index.md', $index);

        // Individual site pages
        foreach ($sites as $site) {
            $slug = $this->sanitize($site->name) . '-' . $site->id;
            $dir = $this->tmpDir . '/content/sites/' . $slug;
            mkdir($dir, 0755, true);

            $name = $this->yamlEscape($site->name);
            $description = $this->yamlEscape($site->description ?? '');
            $owner = $this->yamlEscape($site->owner?->name ?? '');

            $md = <<<MD
---
title: {$name}
description: {$description}
owner: {$owner}
latitude: {$site->latitude}
longitude: {$site->longitude}
---

Site geographique de suivi phenologique.
MD;

            file_put_contents($dir . '/index.md', $md);
        }
    }

    private function writePlantsContent(\Illuminate\Support\Collection $plants): void
    {
        // Plants list page
        $rows = '';
        foreach ($plants as $plant) {
            $name = e($plant->name);
            $slug = $this->sanitize($plant->name) . '-' . $plant->id;
            $taxon = e($plant->taxon?->binomial_name ?? '-');
            $site = e($plant->site?->name ?? '-');
            $status = $plant->status ?? '-';
            $obsCount = $plant->observations_count ?? 0;
            $statusClass = $status === 'alive' ? 'badge-green' : ($status === 'dead' ? 'badge-red' : 'badge-gray');
            $rows .= "<tr><td><a href=\"/plantes/{$slug}/\">{$name}</a></td><td><em>{$taxon}</em></td><td>{$site}</td><td><span class=\"badge {$statusClass}\">{$status}</span></td><td>{$obsCount}</td></tr>\n";
        }

        $index = <<<MD
---
title: "Plantes"
---

<table>
<thead><tr><th>Nom</th><th>Taxon</th><th>Site</th><th>Statut</th><th>Obs.</th></tr></thead>
<tbody>
{$rows}
</tbody>
</table>
MD;

        file_put_contents($this->tmpDir . '/content/plantes/_index.md', $index);

        // Individual plant pages
        foreach ($plants as $plant) {
            $slug = $this->sanitize($plant->name) . '-' . $plant->id;
            $dir = $this->tmpDir . '/content/plantes/' . $slug;
            mkdir($dir, 0755, true);

            // Photo paths
            $photoPaths = [];
            foreach ($plant->photos as $photo) {
                if ($photo->image) {
                    $ext = pathinfo($photo->image, PATHINFO_EXTENSION) ?: 'jpg';
                    $photoFile = "plant_{$plant->id}_photo_{$photo->id}.{$ext}";
                    $photoPaths[] = '/images/plants/' . $photoFile;
                }
            }

            // Observations data for template
            $obsData = [];
            foreach ($plant->observations as $obs) {
                $obsData[] = [
                    'date' => $obs->observation_date?->format('Y-m-d') ?? '-',
                    'stage' => $obs->phenologicalStage?->stage_code ?? '-',
                    'observer' => $obs->observer?->name ?? '-',
                    'notes' => Str::limit($obs->notes ?? '', 100),
                ];
            }

            // Cultivation profile
            $cultivation = null;
            if ($plant->cultivationProfile) {
                $cp = $plant->cultivationProfile;
                $cultivation = [
                    'soil_type' => $cp->soil_type ?? '',
                    'sun_exposure' => $cp->sun_exposure ?? '',
                    'watering' => $cp->watering ?? '',
                    'hardiness_zone' => $cp->hardiness_zone ?? '',
                    'notes' => $cp->notes ?? '',
                ];
            }

            $tags = $plant->userTags->pluck('name')->toArray();

            // Build YAML front matter as array then encode
            $frontMatter = [
                'title' => $plant->name ?? 'Sans nom',
                'type' => 'plantes',
                'status' => $plant->status ?? 'alive',
                'identification_certainty' => $plant->identification_certainty ?? 'certain',
                'taxon' => $plant->taxon?->binomial_name,
                'common_name' => $plant->taxon?->common_name_fr,
                'family' => $plant->taxon?->family,
                'genus' => $plant->taxon?->genus,
                'species' => $plant->taxon?->species,
                'site_name' => $plant->site?->name,
                'site_slug' => $plant->site ? ($this->sanitize($plant->site->name) . '-' . $plant->site->id) : null,
                'category' => $plant->category?->name,
                'cultivar' => $plant->cultivar,
                'variety' => $plant->variety,
                'cultivar_ref' => $plant->cultivarRef?->name,
                'planting_date' => $plant->planting_date?->format('Y-m-d'),
                'height_category' => $plant->height_category ? (Plant::HEIGHT_CATEGORIES[$plant->height_category] ?? $plant->height_category) : null,
                'health_status' => $plant->health_status ? (Plant::HEALTH_STATUSES[$plant->health_status] ?? $plant->health_status) : null,
                'abundance' => $plant->abundance,
                'owner' => $plant->owner?->name,
                'tags' => $tags ?: null,
                'notes' => $plant->notes,
                'care_notes' => $plant->care_notes,
                'ecological_notes' => $plant->ecological_notes,
                'anecdotes' => $plant->anecdotes,
                'cultural_significance' => $plant->cultural_significance,
                'photos' => $photoPaths ?: null,
                'observations_data' => $obsData ?: null,
                'cultivation' => $cultivation,
                'latitude' => $plant->latitude,
                'longitude' => $plant->longitude,
            ];

            // Remove null values
            $frontMatter = array_filter($frontMatter, fn ($v) => $v !== null && $v !== '' && $v !== []);

            $yaml = $this->arrayToYaml($frontMatter);

            $md = "---\n{$yaml}---\n";

            file_put_contents($dir . '/index.md', $md);
        }
    }

    private function writeObservationsContent(\Illuminate\Support\Collection $observations): void
    {
        // Observations list page
        $rows = '';
        foreach ($observations as $obs) {
            $date = $obs->observation_date?->format('Y-m-d') ?? '-';
            $plantName = e($obs->plant?->name ?? '-');
            $plantSlug = $obs->plant ? ($this->sanitize($obs->plant->name) . '-' . $obs->plant->id) : '';
            $stage = e($obs->phenologicalStage?->stage_code ?? '-');
            $observer = e($obs->observer?->name ?? '-');
            $rows .= "<tr><td>{$date}</td><td><a href=\"/plantes/{$plantSlug}/\">{$plantName}</a></td><td><span class=\"badge badge-blue\">{$stage}</span></td><td>{$observer}</td></tr>\n";
        }

        $index = <<<MD
---
title: "Observations"
---

<table>
<thead><tr><th>Date</th><th>Plante</th><th>Stade</th><th>Observateur</th></tr></thead>
<tbody>
{$rows}
</tbody>
</table>
MD;

        file_put_contents($this->tmpDir . '/content/observations/_index.md', $index);
    }

    // ── YAML helper ─────────────────────────────────────────

    private function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml = '';
        $prefix = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value) && !empty($value)) {
                // Check if sequential array
                if (array_keys($value) === range(0, count($value) - 1)) {
                    // Check if array of arrays (list of objects)
                    if (is_array($value[0])) {
                        $yaml .= "{$prefix}{$key}:\n";
                        foreach ($value as $item) {
                            $first = true;
                            foreach ($item as $k => $v) {
                                if ($first) {
                                    $yaml .= "{$prefix}  - {$k}: " . $this->yamlValue($v) . "\n";
                                    $first = false;
                                } else {
                                    $yaml .= "{$prefix}    {$k}: " . $this->yamlValue($v) . "\n";
                                }
                            }
                        }
                    } else {
                        // Simple list
                        $yaml .= "{$prefix}{$key}:\n";
                        foreach ($value as $item) {
                            $yaml .= "{$prefix}  - " . $this->yamlValue($item) . "\n";
                        }
                    }
                } else {
                    // Associative array (map)
                    $yaml .= "{$prefix}{$key}:\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= "{$prefix}{$key}: " . $this->yamlValue($value) . "\n";
            }
        }

        return $yaml;
    }

    private function yamlValue($value): string
    {
        if ($value === null) {
            return '""';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $this->yamlEscape((string) $value);
    }

    // ── Copy photos ─────────────────────────────────────────

    private function copyPlantPhotos(\Illuminate\Support\Collection $plants): void
    {
        foreach ($plants as $plant) {
            foreach ($plant->photos as $photo) {
                if (! $photo->image) {
                    continue;
                }
                $srcPath = $this->resolveImagePath($photo->image);
                if ($srcPath && file_exists($srcPath)) {
                    $ext = pathinfo($photo->image, PATHINFO_EXTENSION) ?: 'jpg';
                    $dest = $this->tmpDir . '/static/images/plants/plant_' . $plant->id . '_photo_' . $photo->id . '.' . $ext;
                    copy($srcPath, $dest);
                }
            }
        }
    }

    private function copyObservationPhotos(\Illuminate\Support\Collection $observations): void
    {
        foreach ($observations as $obs) {
            foreach ($obs->photos as $photo) {
                if (! $photo->image) {
                    continue;
                }
                $srcPath = $this->resolveImagePath($photo->image);
                if ($srcPath && file_exists($srcPath)) {
                    $ext = pathinfo($photo->image, PATHINFO_EXTENSION) ?: 'jpg';
                    $dest = $this->tmpDir . '/static/images/observations/obs_' . $obs->id . '_photo_' . $photo->id . '.' . $ext;
                    copy($srcPath, $dest);
                }
            }
        }
    }

    // ── ZIP ─────────────────────────────────────────────────

    private function buildZip(string $zipPath): void
    {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->addDirectoryToZip($zip, $this->tmpDir, 'phenolab-hugo');

        $zip->close();
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipPrefix): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $zipPrefix . '/' . substr($filePath, strlen($dir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }

        rmdir($dir);
    }
}
