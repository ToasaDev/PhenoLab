<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteGeoJsonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [(float) $this->longitude, (float) $this->latitude],
            ],
            'properties' => [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'altitude' => $this->altitude,
                'environment' => $this->environment,
                'is_private' => $this->is_private,
                'plants_count' => $this->plants_count ?? 0,
                'owner' => $this->whenLoaded('owner', fn () => [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                ]),
            ],
        ];
    }
}
