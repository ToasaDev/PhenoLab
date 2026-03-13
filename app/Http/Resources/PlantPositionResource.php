<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantPositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'label' => $this->label,
            'description' => $this->description,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'gps_accuracy' => $this->gps_accuracy,
            'site_position_x' => $this->site_position_x,
            'site_position_y' => $this->site_position_y,
            'soil_notes' => $this->soil_notes,
            'exposure_notes' => $this->exposure_notes,
            'microclimate_notes' => $this->microclimate_notes,
            'is_active' => $this->is_active,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'plants_count' => $this->plants_count ?? $this->plants()->count(),
            'current_plant_name' => $this->plants()->where('status', 'alive')->first()?->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
