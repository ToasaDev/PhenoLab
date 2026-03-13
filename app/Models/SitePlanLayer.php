<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SitePlanLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'start_date',
        'end_date',
        'is_active',
        'drawing_overlay',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date'      => 'date',
            'end_date'        => 'date',
            'is_active'       => 'boolean',
            'drawing_overlay' => 'array',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class, 'layer_id');
    }

    // ── Helper methods ──────────────────────────────────────────────

    /**
     * Check if this layer is valid at a given date.
     */
    public function isValidAt(Carbon|string $date): bool
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        if ($date->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }

        return $this->is_active;
    }
}
