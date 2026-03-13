<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'category_type' => $this->category_type,
            'plants_count' => $this->whenCounted('plants', $this->plants_count ?? null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
