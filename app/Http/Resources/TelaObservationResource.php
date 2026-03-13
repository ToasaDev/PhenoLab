<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelaObservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'year' => $this->year,
            'day_of_year' => $this->day_of_year,
            'data_source' => $this->data_source,
            'site_id' => $this->site_id_tela,
            'site_name' => $this->site_name,
            'site_latitude' => $this->site_latitude,
            'site_longitude' => $this->site_longitude,
            'site_altitude' => $this->site_altitude,
            'site_altitude_from_ign' => $this->site_altitude_from_ign,
            'taxon_id' => $this->taxon_id_tela,
            'binomial_name' => $this->binomial_name,
            'kingdom' => $this->kingdom,
            'genus' => $this->genus,
            'species' => $this->species,
            'subspecies' => $this->subspecies,
            'variety' => $this->variety,
            'taxon_clone_or_accession_code' => $this->taxon_clone_or_accession_code,
            'phenological_scale_id' => $this->phenological_scale_id,
            'phenological_scale' => $this->phenological_scale,
            'stage_code' => $this->stage_code,
            'stage_description' => $this->stage_description,
            'phenological_main_event_code' => $this->phenological_main_event_code,
            'phenological_main_event_description' => $this->phenological_main_event_description,
            'data_license_acronym' => $this->data_license_acronym,
            'data_license' => $this->data_license,
            'data_license_url' => $this->data_license_url,
            'contact_name' => $this->contact_name,
            'contact_email_address' => $this->contact_email_address,
            'contact_organisation' => $this->contact_organisation,
            'environment' => $this->environment,
            'private_station' => $this->private_station,
            'observation_number' => $this->observation_number,
            'observer_number' => $this->observer_number,
            'aggregation' => $this->aggregation,
            'drias_cell_number' => $this->drias_cell_number,
            'safran_cell_number' => $this->safran_cell_number,
            'created_at' => $this->created_at,
        ];
    }
}
