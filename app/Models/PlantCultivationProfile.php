<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantCultivationProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        // WHEN
        'planting_months',
        'sowing_months',
        'harvest_months',
        'flowering_months',
        // WHERE
        'exposure',
        'hardiness_min',
        'usda_zone',
        'suitable_environments',
        'soil_types',
        'soil_ph',
        'soil_drainage',
        'soil_fertility',
        'mature_height_min',
        'mature_height_max',
        'mature_spread_min',
        'mature_spread_max',
        // CARE
        'watering_needs',
        'watering_notes',
        'fertilizing_frequency',
        'fertilizing_notes',
        'pruning_period',
        'pruning_notes',
        'mulching',
        'winter_protection',
        'pest_susceptibility',
        'disease_susceptibility',
        'companion_plants',
        'avoid_near',
        'propagation_methods',
        'cultivation_difficulty',
        'usage_types',
        'is_edible',
        'is_toxic',
        // META
        'notes',
        'source',
        'extra',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'planting_months'       => 'array',
            'sowing_months'         => 'array',
            'harvest_months'        => 'array',
            'flowering_months'      => 'array',
            'suitable_environments' => 'array',
            'soil_types'            => 'array',
            'companion_plants'      => 'array',
            'avoid_near'            => 'array',
            'usage_types'           => 'array',
            'extra'                 => 'array',
            'is_edible'             => 'boolean',
            'is_toxic'              => 'boolean',
            'mature_height_min'     => 'decimal:2',
            'mature_height_max'     => 'decimal:2',
            'mature_spread_min'     => 'decimal:2',
            'mature_spread_max'     => 'decimal:2',
        ];
    }

    // ── Vocabulary constants ────────────────────────────────────────

    public const EXPOSURES = [
        'full_sun'      => 'Plein soleil',
        'partial_shade' => 'Mi-ombre',
        'shade'         => 'Ombre',
        'full_shade'    => 'Ombre dense',
    ];

    public const WATERING_NEEDS = [
        'low'      => 'Faible',
        'moderate' => 'Modéré',
        'regular'  => 'Régulier',
        'high'     => 'Élevé',
    ];

    public const DIFFICULTIES = [
        'easy'   => 'Facile',
        'medium' => 'Moyen',
        'hard'   => 'Difficile',
        'expert' => 'Expert',
    ];

    public const SOIL_TYPES = [
        'clay'   => 'Argileux',
        'sandy'  => 'Sableux',
        'loam'   => 'Limoneux',
        'chalky' => 'Calcaire',
        'peaty'  => 'Tourbeux',
        'silty'  => 'Limono-argileux',
    ];

    public const SOIL_DRAINAGE = [
        'well_drained' => 'Bien drainé',
        'moist'        => 'Frais / humide',
        'wet'          => 'Mouillé',
        'dry'          => 'Sec',
    ];

    public const SOIL_FERTILITY = [
        'poor'    => 'Pauvre',
        'average' => 'Moyen',
        'rich'    => 'Riche',
    ];

    public const USAGE_TYPES = [
        'ornamental' => 'Ornemental',
        'edible'     => 'Comestible',
        'medicinal'  => 'Médicinal',
        'hedging'    => 'Haie / brise-vent',
        'shade'      => 'Ombrage',
        'fragrance'  => 'Parfum',
        'wildlife'   => 'Faune / pollinisateurs',
        'erosion'    => 'Anti-érosion',
        'timber'     => 'Bois d\'œuvre',
        'fodder'     => 'Fourrage',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
