<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'taxon_id' => $this->taxon_id,
            'kingdom' => $this->kingdom,
            'phylum' => $this->phylum,
            'class_name' => $this->class_name,
            'order' => $this->order,
            'family' => $this->family,
            'genus' => $this->genus,
            'species' => $this->species,
            'binomial_name' => $this->binomial_name,
            'subspecies' => $this->subspecies,
            'variety' => $this->variety,
            'cultivar' => $this->cultivar,
            'common_name_fr' => $this->common_name_fr,
            'common_name_it' => $this->common_name_it,
            'common_name_en' => $this->common_name_en,
            'author' => $this->author,
            'publication_year' => $this->publication_year,
            'gbif_id' => $this->gbif_id,
            'gbif_status' => $this->gbif_status,
            'gbif_rank' => $this->gbif_rank,
            'gbif_canonical_name' => $this->gbif_canonical_name,
            'gbif_synced_at' => $this->gbif_synced_at,
            'display_name' => $this->common_name_fr ?: $this->binomial_name,
            'full_name' => $this->getFullName(),
            'plants_count' => $this->plants_count ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function getFullName(): string
    {
        $name = $this->binomial_name;
        if ($this->subspecies) $name .= ' subsp. ' . $this->subspecies;
        if ($this->variety) $name .= ' var. ' . $this->variety;
        if ($this->cultivar) $name .= " '" . $this->cultivar . "'";
        if ($this->author) $name .= ' ' . $this->author;
        return $name;
    }
}
