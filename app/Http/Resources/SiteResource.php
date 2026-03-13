<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'altitude' => $this->altitude,
            'environment' => $this->environment,
            'is_private' => $this->is_private,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'username' => $this->owner->name,
            ]),
            'soil_type' => $this->soil_type,
            'exposure' => $this->exposure,
            'slope' => $this->slope,
            'climate_zone' => $this->climate_zone,
            'site_plan_image' => $this->site_plan_image ? asset('storage/' . $this->site_plan_image) : null,
            'plan_width_meters' => $this->plan_width_meters,
            'plan_height_meters' => $this->plan_height_meters,
            'drawing_overlay' => $this->drawing_overlay,
            'plants_count' => $this->plants_count ?? null,
            'observations_count' => $this->observations_count ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
