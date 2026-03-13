<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObservationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'observation_date' => $this->observation_date?->toDateString(),
            'plant_id' => $this->plant_id,
            'plant_name' => $this->whenLoaded('plant', fn () => $this->plant->name),
            'plant_taxon_binomial_name' => $this->whenLoaded('plant', fn () => $this->plant->taxon?->binomial_name),
            'plant_taxon_common_name_fr' => $this->whenLoaded('plant', fn () => $this->plant->taxon?->common_name_fr),
            'plant_site_name' => $this->whenLoaded('plant', fn () => $this->plant->site?->name),
            'plant_category_name' => $this->whenLoaded('plant', fn () => $this->plant->category?->name),
            'stage_code' => $this->whenLoaded('phenologicalStage', fn () => $this->phenologicalStage->stage_code),
            'stage_description' => $this->whenLoaded('phenologicalStage', fn () => $this->phenologicalStage->stage_description),
            'observer_id' => $this->observer_id,
            'observer_username' => $this->whenLoaded('observer', fn () => $this->observer->name),
            'intensity' => $this->intensity,
            'temperature' => $this->temperature,
            'weather_condition' => $this->weather_condition,
            'confidence_level' => $this->confidence_level,
            'is_validated' => $this->is_validated,
            'is_public' => $this->is_public,
            'photos_count' => $this->photos_count ?? null,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
