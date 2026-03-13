<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'taxon' => new TaxonResource($this->whenLoaded('taxon')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'site' => new SiteResource($this->whenLoaded('site')),
            'position' => new PlantPositionResource($this->whenLoaded('position')),
            'planting_date' => $this->planting_date?->toDateString(),
            'age_years' => $this->age_years,
            'height_category' => $this->height_category,
            'exact_height' => $this->exact_height,
            'health_status' => $this->health_status,
            'status' => $this->status,
            'death_date' => $this->death_date?->toDateString(),
            'death_cause' => $this->death_cause,
            'death_notes' => $this->death_notes,
            'clone_or_accession' => $this->clone_or_accession,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'username' => $this->owner->name,
            ]),
            'is_private' => $this->is_private,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'gps_accuracy' => $this->gps_accuracy,
            'site_position_x' => $this->site_position_x,
            'site_position_y' => $this->site_position_y,
            'map_position_x' => $this->map_position_x,
            'map_position_y' => $this->map_position_y,
            'layer_id' => $this->layer_id,
            'notes' => $this->notes,
            'anecdotes' => $this->anecdotes,
            'cultural_significance' => $this->cultural_significance,
            'ecological_notes' => $this->ecological_notes,
            'care_notes' => $this->care_notes,
            'observations_count' => $this->observations_count ?? $this->observations()->count(),
            'photos_count' => $this->photos_count ?? $this->photos()->count(),
            'last_observation' => $this->whenLoaded('observations', function () {
                $last = $this->observations->sortByDesc('observation_date')->first();
                return $last ? [
                    'id' => $last->id,
                    'observation_date' => $last->observation_date->toDateString(),
                    'stage_code' => $last->phenologicalStage?->stage_code,
                    'stage_description' => $last->phenologicalStage?->stage_description,
                ] : null;
            }),
            'coordinates' => $this->latitude && $this->longitude ? [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ] : null,
            'replaces_plant' => $this->whenLoaded('replaces', fn () => $this->replaces ? [
                'id' => $this->replaces->id,
                'name' => $this->replaces->name,
            ] : null),
            'replaced_by' => $this->whenLoaded('replacedBy', fn () => $this->replacedBy ? [
                'id' => $this->replacedBy->id,
                'name' => $this->replacedBy->name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
