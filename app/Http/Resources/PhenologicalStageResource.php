<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhenologicalStageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stage_code' => $this->stage_code,
            'stage_description' => $this->stage_description,
            'main_event_code' => $this->main_event_code,
            'main_event_description' => $this->main_event_description,
            'phenological_scale' => $this->phenological_scale,
            'observations_count' => $this->whenCounted('observations', $this->observations_count ?? null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
