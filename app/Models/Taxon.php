<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Taxon extends Model
{
    use HasFactory;

    protected $table = 'taxons';

    protected $fillable = [
        'taxon_id',
        'kingdom',
        'phylum',
        'class_name',
        'order',
        'family',
        'genus',
        'species',
        'binomial_name',
        'subspecies',
        'variety',
        'cultivar',
        'common_name_fr',
        'common_name_it',
        'common_name_en',
        'author',
        'publication_year',
        'gbif_id',
        'gbif_status',
        'gbif_rank',
        'gbif_canonical_name',
        'gbif_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'gbif_id'         => 'integer',
            'gbif_synced_at'  => 'datetime',
        ];
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (Taxon $taxon) {
            if ($taxon->genus && $taxon->species) {
                $taxon->binomial_name = "{$taxon->genus} {$taxon->species}";
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class);
    }

    public function alternativeNames(): HasMany
    {
        return $this->hasMany(CommonName::class);
    }

    // ── Accessors ───────────────────────────────────────────────────

    /**
     * Full taxonomic name including subspecies, variety, cultivar and author.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $name = $this->binomial_name ?? '';

                if ($this->subspecies) {
                    $name .= " subsp. {$this->subspecies}";
                }
                if ($this->variety) {
                    $name .= " var. {$this->variety}";
                }
                if ($this->cultivar) {
                    $name .= " '{$this->cultivar}'";
                }
                if ($this->author) {
                    $name .= " {$this->author}";
                }

                return $name;
            },
        );
    }

    /**
     * Display name: common name (binomial) or just binomial.
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->common_name_fr
                ? "{$this->common_name_fr} ({$this->binomial_name})"
                : $this->binomial_name,
        );
    }

    /**
     * Get common name in specified language.
     */
    public function getCommonName(string $language = 'fr'): string
    {
        $names = [
            'fr' => $this->common_name_fr,
            'it' => $this->common_name_it,
            'en' => $this->common_name_en,
        ];

        return $names[$language] ?? $this->binomial_name ?? '';
    }
}
