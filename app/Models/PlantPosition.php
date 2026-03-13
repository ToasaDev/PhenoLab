<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'label',
        'description',
        'latitude',
        'longitude',
        'gps_accuracy',
        'gps_recorded_at',
        'site_position_x',
        'site_position_y',
        'soil_notes',
        'exposure_notes',
        'microclimate_notes',
        'is_active',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude'        => 'decimal:8',
            'longitude'       => 'decimal:8',
            'gps_accuracy'    => 'float',
            'gps_recorded_at' => 'datetime',
            'is_active'       => 'boolean',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class, 'position_id');
    }

    // ── Accessors ───────────────────────────────────────────────────

    /**
     * Get the currently living plant at this position.
     */
    protected function currentPlant(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->plants()->where('status', 'alive')->first(),
        );
    }

    /**
     * Get all plants that have ever been at this position, ordered by planting date.
     */
    protected function successionHistory(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->plants()->orderBy('planting_date')->get(),
        );
    }
}
