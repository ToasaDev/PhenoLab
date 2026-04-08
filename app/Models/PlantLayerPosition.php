<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantLayerPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'layer_id',
        'plant_id',
        'map_position_x',
        'map_position_y',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'map_position_x' => 'float',
            'map_position_y' => 'float',
        ];
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(SitePlanLayer::class, 'layer_id');
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
