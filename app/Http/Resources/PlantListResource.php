<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'taxon_id' => $this->taxon_id,
            'taxon_binomial_name' => $this->whenLoaded('taxon', fn () => $this->taxon->binomial_name),
            'taxon_common_name_fr' => $this->whenLoaded('taxon', fn () => $this->taxon->common_name_fr),
            'taxon_family' => $this->whenLoaded('taxon', fn () => $this->taxon->family),
            'taxon_genus' => $this->whenLoaded('taxon', fn () => $this->taxon->genus),
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'site_id' => $this->site_id,
            'site_name' => $this->whenLoaded('site', fn () => $this->site->name),
            'health_status' => $this->health_status,
            'status' => $this->status,
            'planting_date' => $this->planting_date?->toDateString(),
            'death_date' => $this->death_date?->toDateString(),
            'is_private' => $this->is_private,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'username' => $this->owner->name,
            ]),
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'observations_count' => $this->observations_count ?? null,
            'photos_count' => $this->photos_count ?? null,
            'last_observation_date' => $this->last_observation_date ?? null,
            'has_predecessor' => $this->replaces_id !== null,
            'has_successor' => (bool) ($this->replaced_by_count ?? false),
            'created_at' => $this->created_at,
        ];
    }
}
