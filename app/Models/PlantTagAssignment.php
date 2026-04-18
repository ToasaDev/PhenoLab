<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantTagAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'plant_id',
        'tag_id',
        'assigned_by_id',
    ];

    // ── Relationships ──────────────────────────────────────

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(UserPlantTag::class, 'tag_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
}
