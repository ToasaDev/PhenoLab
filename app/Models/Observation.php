<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Observation extends Model
{
    use HasFactory;

    /**
     * Serialize date columns without timezone to avoid offset issues in the frontend.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $fillable = [
        'observation_date',
        'plant_id',
        'phenological_stage_id',
        'observer_id',
        'intensity',
        'temperature',
        'weather_condition',
        'humidity',
        'wind_speed',
        'notes',
        'confidence_level',
        'is_validated',
        'validated_by_id',
        'validation_date',
        'time_of_day',
        'day_of_year',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'observation_date' => 'date',
            'intensity'        => 'integer',
            'temperature'      => 'float',
            'humidity'         => 'integer',
            'wind_speed'       => 'float',
            'confidence_level' => 'integer',
            'is_validated'     => 'boolean',
            'validation_date'  => 'datetime',
            'time_of_day'      => 'string',
            'is_public'        => 'boolean',
        ];
    }

    /**
     * Intensity level options (percentage brackets).
     */
    public const INTENSITY_LEVELS = [
        1 => 'Tres faible (<10%)',
        2 => 'Faible (10-25%)',
        3 => 'Modere (25-50%)',
        4 => 'Fort (50-75%)',
        5 => 'Tres fort (>75%)',
    ];

    /**
     * Weather condition options.
     */
    public const WEATHER_CONDITIONS = [
        'ensoleillé' => 'Ensoleillé',
        'nuageux'    => 'Nuageux',
        'pluvieux'   => 'Pluvieux',
        'venteux'    => 'Venteux',
        'orageux'    => 'Orageux',
    ];

    /**
     * Confidence level options.
     */
    public const CONFIDENCE_LEVELS = [
        1 => 'Peu sur',
        2 => 'Moyennement sur',
        3 => 'Sur',
        4 => 'Tres sur',
        5 => 'Certain',
    ];

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (Observation $observation) {
            if ($observation->observation_date) {
                $observation->day_of_year = (int) $observation->observation_date->format('z') + 1;
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function phenologicalStage(): BelongsTo
    {
        return $this->belongsTo(PhenologicalStage::class);
    }

    public function observer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'observer_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ObservationPhoto::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    /**
     * Filter to public observations only.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Filter observations for a given plant.
     */
    public function scopeForPlant(Builder $query, int $plantId): Builder
    {
        return $query->where('plant_id', $plantId);
    }

    /**
     * Filter observations for a given year.
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('observation_date', $year);
    }
}
