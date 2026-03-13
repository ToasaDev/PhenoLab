<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'observation_date' => $this->observation_date?->toDateString(),
            'plant' => new PlantListResource($this->whenLoaded('plant')),
            'phenological_stage' => new PhenologicalStageResource($this->whenLoaded('phenologicalStage')),
            'observer' => $this->whenLoaded('observer', fn () => [
                'id' => $this->observer->id,
                'username' => $this->observer->name,
            ]),
            'intensity' => $this->intensity,
            'temperature' => $this->temperature,
            'weather_condition' => $this->weather_condition,
            'humidity' => $this->humidity,
            'wind_speed' => $this->wind_speed,
            'notes' => $this->notes,
            'confidence_level' => $this->confidence_level,
            'is_validated' => $this->is_validated,
            'validated_by' => $this->whenLoaded('validatedBy', fn () => $this->validatedBy ? [
                'id' => $this->validatedBy->id,
                'username' => $this->validatedBy->name,
            ] : null),
            'validation_date' => $this->validation_date,
            'time_of_day' => $this->time_of_day,
            'day_of_year' => $this->day_of_year,
            'is_public' => $this->is_public,
            'photos' => PlantPhotoResource::collection($this->whenLoaded('photos')),
            'photos_count' => $this->photos_count ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
