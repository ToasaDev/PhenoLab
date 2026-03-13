<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Plant extends Model
{
    use HasFactory;

    /**
     * Serialize date columns as Y-m-d to avoid timezone offset issues in the frontend.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $fillable = [
        'name',
        'description',
        'taxon_id',
        'category_id',
        'site_id',
        'position_id',
        'planting_date',
        'age_years',
        'height_category',
        'exact_height',
        'health_status',
        'status',
        'death_date',
        'death_cause',
        'death_notes',
        'replaces_id',
        'clone_or_accession',
        'owner_id',
        'is_private',
        'latitude',
        'longitude',
        'gps_accuracy',
        'gps_recorded_at',
        'site_position_x',
        'site_position_y',
        'map_position_x',
        'map_position_y',
        'layer_id',
        'notes',
        'anecdotes',
        'cultural_significance',
        'ecological_notes',
        'care_notes',
    ];

    protected function casts(): array
    {
        return [
            'planting_date'   => 'date',
            'death_date'      => 'date',
            'is_private'      => 'boolean',
            'latitude'        => 'decimal:8',
            'longitude'       => 'decimal:8',
            'gps_accuracy'    => 'float',
            'gps_recorded_at' => 'datetime',
            'map_position_x'  => 'float',
            'map_position_y'  => 'float',
        ];
    }

    /**
     * Height category options.
     */
    public const HEIGHT_CATEGORIES = [
        'seedling' => 'Plantule (<30cm)',
        'young'    => 'Jeune (30cm-1m)',
        'medium'   => 'Moyen (1-3m)',
        'mature'   => 'Mature (3-10m)',
        'large'    => 'Grand (>10m)',
    ];

    /**
     * Health status options.
     */
    public const HEALTH_STATUSES = [
        'excellent' => 'Excellent',
        'good'      => 'Bon',
        'fair'      => 'Correct',
        'poor'      => 'Mauvais',
        'dead'      => 'Mort',
    ];

    /**
     * Lifecycle status options.
     */
    public const STATUSES = [
        'alive'    => 'Vivant',
        'dead'     => 'Mort',
        'replaced' => 'Remplace',
        'removed'  => 'Retire',
    ];

    /**
     * Death cause options.
     */
    public const DEATH_CAUSES = [
        'disease'  => 'Maladie',
        'pests'    => 'Ravageurs',
        'frost'    => 'Gel',
        'drought'  => 'Secheresse',
        'flooding' => 'Inondation',
        'wind'     => 'Vent/Tempete',
        'age'      => 'Vieillesse',
        'accident' => 'Accident',
        'human'    => 'Intervention humaine',
        'unknown'  => 'Cause inconnue',
        'other'    => 'Autre',
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(PlantPosition::class, 'position_id');
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SitePlanLayer::class, 'layer_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PlantPhoto::class);
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_id');
    }

    public function replacedBy(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────

    /**
     * Filter to alive plants only.
     */
    public function scopeAlive(Builder $query): Builder
    {
        return $query->where('status', 'alive');
    }

    /**
     * Filter to public plants only.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_private', false);
    }

    /**
     * Filter plants belonging to a given site.
     */
    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Filter plants within a radius (km) from given coordinates using Haversine formula.
     */
    public function scopeNearby(Builder $query, float $latitude, float $longitude, float $radiusKm = 10): Builder
    {
        $haversine = "(
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))
            )
        )";

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("*, {$haversine} AS distance", [$latitude, $longitude, $latitude])
            ->whereRaw("{$haversine} < ?", [$latitude, $longitude, $latitude, $radiusKm])
            ->orderByRaw("{$haversine} ASC", [$latitude, $longitude, $latitude]);
    }

    // ── Helper methods ──────────────────────────────────────────────

    /**
     * Get plant coordinates as [lat, lng] for mapping.
     * Precedence: position > plant latitude/longitude.
     */
    public function getCoordinates(): ?array
    {
        if ($this->position && $this->position->latitude && $this->position->longitude) {
            return [$this->position->latitude, $this->position->longitude];
        }

        if ($this->latitude && $this->longitude) {
            return [$this->latitude, $this->longitude];
        }

        return null;
    }

    /**
     * Calculate distance from site center in meters using Haversine.
     */
    public function distanceFromSiteCenter(): ?float
    {
        $plantCoords = $this->getCoordinates();
        $siteCoords = $this->site?->coordinates;

        if (! $plantCoords || ! $siteCoords) {
            return null;
        }

        $lat1 = deg2rad($siteCoords[0]);
        $lon1 = deg2rad($siteCoords[1]);
        $lat2 = deg2rad($plantCoords[0]);
        $lon2 = deg2rad($plantCoords[1]);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return $c * 6_371_000; // Earth radius in meters
    }
}
