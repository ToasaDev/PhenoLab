<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        'action_type_id',
        'action_date',
        'title',
        'notes',
        'product_name',
        'quantity',
        'unit',
        'dosage',
        'method',
        'performed_by',
        'performer_name',
        'cost',
        'weather_conditions',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'date',
            'quantity'    => 'decimal:2',
            'cost'        => 'decimal:2',
            'is_private'  => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function actionType(): BelongsTo
    {
        return $this->belongsTo(PlantActionType::class, 'action_type_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ── Accessors ──────────────────────────────────────────────

    public function getPerformerDisplayAttribute(): string
    {
        return $this->performer?->name ?? $this->performer_name ?? 'Non renseigné';
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeForPlant($query, int $plantId)
    {
        return $query->where('plant_id', $plantId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('action_date', $year);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }
}
