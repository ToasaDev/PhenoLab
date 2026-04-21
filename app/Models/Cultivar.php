<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cultivar extends Model
{
    protected $fillable = [
        'name',
        'full_name',
        'taxon_id',
        'upov_code',
        'wikidata_id',
        'type',
        'synonyms',
        'origin_country',
        'origin_region',
        'breeder',
        'year_introduced',
        'parentage',
        'fruit_color',
        'fruit_size',
        'fruit_shape',
        'flesh_color',
        'flesh_texture',
        'flavor_profile',
        'skin_type',
        'harvest_period',
        'flowering_period',
        'maturity_group',
        'storage_life',
        'vigor',
        'productivity',
        'self_fertile',
        'pollinators',
        'rootstocks',
        'disease_resistance',
        'cold_hardiness',
        'usage_types',
        'image_url',
        'description',
        'notes',
        'source',
        'registration_country',
        'registration_status',
        'registration_date',
        'national_id',
        'eupvp_uuid',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'self_fertile'      => 'boolean',
            'registration_date' => 'date',
            'extra'             => 'array',
        ];
    }

    // ── Relationships ───────────────────────────────────────

    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class);
    }

    // ── Scopes ──────────────────────────────────────────────

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);

        return $query->where(function ($q) use ($escaped) {
            $q->where('name', 'like', "%{$escaped}%")
              ->orWhere('synonyms', 'like', "%{$escaped}%")
              ->orWhere('full_name', 'like', "%{$escaped}%");
        });
    }

    public function scopeForTaxon(Builder $query, int $taxonId): Builder
    {
        return $query->where('taxon_id', $taxonId);
    }

    public function scopeForUpovCode(Builder $query, string $code): Builder
    {
        return $query->where('upov_code', $code);
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('registration_status', 'Registered');
    }
}
