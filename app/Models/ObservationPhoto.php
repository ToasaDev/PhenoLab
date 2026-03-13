<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ObservationPhoto extends Model
{
    use HasFactory;

    protected $appends = ['image_url'];

    protected $fillable = [
        'observation_id',
        'image',
        'title',
        'description',
        'photo_type',
        'photographer_id',
        'width',
        'height',
        'file_size',
        'display_order',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Observation photo type options.
     */
    public const PHOTO_TYPES = [
        'phenological_state' => 'Etat phenologique',
        'detail'             => 'Detail',
        'comparison'         => 'Comparaison',
        'context'            => 'Contexte',
        'measurement'        => 'Mesure',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? url("/api/v1/observation-photos/{$this->id}/image") : null;
    }

    // ── Relationships ───────────────────────────────────────────────

    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class);
    }

    public function photographer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }
}
