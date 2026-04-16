<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'latitude',
        'longitude',
        'altitude',
        'environment',
        'site_category_id',
        'is_private',
        'owner_id',
        'soil_type',
        'exposure',
        'slope',
        'climate_zone',
        'site_plan_image',
        'plan_width_meters',
        'plan_height_meters',
        'drawing_overlay',
    ];

    protected function casts(): array
    {
        return [
            'latitude'           => 'float',
            'longitude'          => 'float',
            'altitude'           => 'float',
            'is_private'         => 'boolean',
            'drawing_overlay'    => 'array',
            'plan_width_meters'  => 'float',
            'plan_height_meters' => 'float',
        ];
    }

    /**
     * Environment type options.
     */
    /**
     * Environment / site type options.
     *
     * Two semantic groups (visually shown via <optgroup> in the SPA):
     *  - Environnements naturels  : urban..agricultural
     *  - Types de site amenages   : botanical_garden..other
     */
    public const ENVIRONMENT_TYPES = [
        // Environnements naturels
        'urban'             => 'Urbain',
        'suburban'          => 'Periurbain',
        'rural'             => 'Rural',
        'forest'            => 'Foret',
        'garden'            => 'Jardin/Parc',
        'natural'           => 'Naturel',
        'agricultural'      => 'Agricole',
        // Types de site amenages
        'botanical_garden'  => 'Jardin botanique',
        'arboretum'         => 'Arboretum',
        'nursery'           => 'Pepiniere',
        'orchard'           => 'Verger',
        'vegetable_garden'  => 'Potager',
        'park'              => 'Parc public',
        'private_garden'    => 'Jardin prive',
        'school_garden'     => 'Jardin pedagogique',
        'community_garden'  => 'Jardin partage',
        'experimental'      => 'Parcelle experimentale',
        'natural_reserve'   => 'Reserve naturelle',
        'other'             => 'Autre',
    ];

    /**
     * Subset of ENVIRONMENT_TYPES considered "natural environment".
     */
    public const ENVIRONMENT_NATURAL = [
        'urban', 'suburban', 'rural', 'forest', 'garden', 'natural', 'agricultural',
    ];

    /**
     * Subset of ENVIRONMENT_TYPES considered "managed site type".
     */
    public const ENVIRONMENT_MANAGED = [
        'botanical_garden', 'arboretum', 'nursery', 'orchard', 'vegetable_garden',
        'park', 'private_garden', 'school_garden', 'community_garden',
        'experimental', 'natural_reserve', 'other',
    ];

    /**
     * Exposure type options.
     */
    public const EXPOSURE_TYPES = [
        'north'     => 'Nord',
        'northeast' => 'Nord-Est',
        'east'      => 'Est',
        'southeast' => 'Sud-Est',
        'south'     => 'Sud',
        'southwest' => 'Sud-Ouest',
        'west'      => 'Ouest',
        'northwest' => 'Nord-Ouest',
    ];

    /**
     * Slope type options.
     */
    public const SLOPE_TYPES = [
        'flat'     => 'Plat',
        'gentle'   => 'Pente douce',
        'moderate' => 'Pente moderee',
        'steep'    => 'Pente forte',
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function siteCategory(): BelongsTo
    {
        return $this->belongsTo(SiteCategory::class, 'site_category_id');
    }

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class);
    }

    public function layers(): HasMany
    {
        return $this->hasMany(SitePlanLayer::class);
    }

    public function plantPositions(): HasMany
    {
        return $this->hasMany(PlantPosition::class);
    }

    // ── Accessors ───────────────────────────────────────────────────

    /**
     * Get site coordinates as [lat, lng] for mapping.
     */
    protected function coordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->latitude && $this->longitude)
                ? [$this->latitude, $this->longitude]
                : null,
        );
    }

    // ── Scopes ──────────────────────────────────────────────────────

    /**
     * Filter to public sites only.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_private', false);
    }

    /**
     * Filter sites within a radius (km) from given coordinates using Haversine formula.
     */
    public function scopeNearby(Builder $query, float $latitude, float $longitude, float $radiusKm = 50): Builder
    {
        $haversine = "(
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))
            )
        )";

        return $query
            ->selectRaw("*, {$haversine} AS distance", [$latitude, $longitude, $latitude])
            ->whereRaw("{$haversine} < ?", [$latitude, $longitude, $latitude, $radiusKm])
            ->orderByRaw("{$haversine} ASC", [$latitude, $longitude, $latitude]);
    }

    // ── Helper methods ──────────────────────────────────────────────

    /**
     * Count plants associated with this site.
     */
    public function plantsCount(): int
    {
        return $this->plants()->count();
    }

    /**
     * Count observations across all plants at this site.
     */
    public function observationsCount(): int
    {
        return Observation::whereHas('plant', fn (Builder $q) => $q->where('site_id', $this->id))->count();
    }
}
