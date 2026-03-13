<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ODSObservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'observation_id' => $this->observation_id_ods,
            'date' => $this->date?->toDateString(),
            'year' => $this->date?->year,
            'day_of_year' => $this->date?->dayOfYear,
            'is_missing' => $this->is_missing,
            'details' => $this->details,
            'creation_date' => $this->creation_date?->toDateString(),
            'update_date' => $this->update_date?->toDateString(),
            'deletion_date' => $this->deletion_date?->toDateString(),
            'species_id' => $this->species_id,
            'vernacular_name' => $this->vernacular_name,
            'scientific_name' => $this->scientific_name,
            'species_type' => $this->species_type,
            'plant_or_animal' => $this->plant_or_animal,
            'individual_id' => $this->individual_id,
            'individual_name' => $this->individual_name,
            'individual_detail' => $this->individual_detail,
            'phenological_stage' => $this->phenological_stage,
            'bbch_code' => $this->bbch_code,
            'station_id' => $this->station_id,
            'station_name' => $this->station_name,
            'station_description' => $this->station_description,
            'station_locality' => $this->station_locality,
            'habitat' => $this->habitat,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'altitude' => $this->altitude,
            'insee_code' => $this->insee_code,
            'department' => $this->department,
            'formatted_location' => $this->latitude && $this->longitude
                ? round($this->latitude, 4) . ', ' . round($this->longitude, 4)
                : null,
        ];
    }
}
