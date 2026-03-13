<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TaxonDetailResource extends TaxonResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'alternative_names' => CommonNameResource::collection($this->whenLoaded('alternativeNames')),
        ]);
    }
}
