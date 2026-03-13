<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ODSObservation extends Model
{
    use HasFactory;

    protected $table = 'ods_observations';

    protected $fillable = [
        'observation_id',
        'date',
        'is_missing',
        'details',
        'creation_date',
        'update_date',
        'deletion_date',
        'species_id',
        'vernacular_name',
        'scientific_name',
        'species_type',
        'plant_or_animal',
        'individual_id',
        'individual_name',
        'individual_detail',
        'phenological_stage',
        'bbch_code',
        'station_id',
        'station_name',
        'station_description',
        'station_locality',
        'habitat',
        'latitude',
        'longitude',
        'altitude',
        'insee_code',
        'department',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'creation_date' => 'date',
            'update_date'   => 'date',
            'deletion_date' => 'date',
            'latitude'      => 'float',
            'longitude'     => 'float',
            'altitude'      => 'float',
        ];
    }

    // ── Accessors ───────────────────────────────────────────────────

    /**
     * Get the year of the observation.
     */
    protected function year(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date?->year,
        );
    }

    /**
     * Get the day of year of the observation.
     */
    protected function dayOfYear(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date ? (int) $this->date->format('z') + 1 : null,
        );
    }

    /**
     * Get formatted geographic coordinates.
     */
    protected function formattedLocation(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->latitude && $this->longitude)
                ? sprintf('%.6f, %.6f', $this->latitude, $this->longitude)
                : '',
        );
    }
}
