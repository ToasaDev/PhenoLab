<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommonName extends Model
{
    use HasFactory;

    protected $fillable = [
        'taxon_id',
        'name',
        'language',
        'region',
        'is_primary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Supported languages.
     */
    public const LANGUAGES = [
        'fr'       => 'Francais',
        'it'       => 'Italien',
        'en'       => 'Anglais',
        'de'       => 'Allemand',
        'es'       => 'Espagnol',
        'pt'       => 'Portugais',
        'ca'       => 'Catalan',
        'oc'       => 'Occitan',
        'regional' => 'Nom regional/dialecte',
        'other'    => 'Autre',
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }
}
