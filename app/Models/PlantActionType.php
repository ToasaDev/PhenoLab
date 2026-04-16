<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PlantActionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'icon',
        'color',
        'applies_to',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Constants ──────────────────────────────────────────────

    public const CATEGORIES = [
        'maintenance'  => 'Entretien',
        'treatment'    => 'Traitement',
        'fertilization' => 'Fertilisation',
        'irrigation'   => 'Irrigation',
        'harvest'      => 'Récolte',
        'planting'     => 'Plantation',
        'protection'   => 'Protection',
        'other'        => 'Autre',
    ];

    public const APPLIES_TO = [
        'all'         => 'Tous',
        'tree'        => 'Arbres',
        'shrub'       => 'Arbustes',
        'vegetable'   => 'Potager',
        'orchard'     => 'Verger',
        'ornamental'  => 'Ornementales',
        'crop'        => 'Cultures',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function actions(): HasMany
    {
        return $this->hasMany(PlantAction::class, 'action_type_id');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeApplicableTo($query, string $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('applies_to', 'all')->orWhere('applies_to', $type);
        });
    }

    // ── Boot ───────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $type) {
            if (empty($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }
}
