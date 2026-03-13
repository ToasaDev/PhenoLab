<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'category_type',
    ];

    /**
     * Available category types.
     */
    public const CATEGORY_TYPES = [
        'trees'   => 'Arbres',
        'shrubs'  => 'Arbustes',
        'plants'  => 'Plantes',
        'animals' => 'Animaux',
        'insects' => 'Insectes',
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    /**
     * Filter categories by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('category_type', $type);
    }
}
