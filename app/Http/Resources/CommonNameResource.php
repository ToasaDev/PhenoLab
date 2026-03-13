<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommonNameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'taxon_id' => $this->taxon_id,
            'name' => $this->name,
            'language' => $this->language,
            'region' => $this->region,
            'is_primary' => $this->is_primary,
            'notes' => $this->notes,
        ];
    }
}
