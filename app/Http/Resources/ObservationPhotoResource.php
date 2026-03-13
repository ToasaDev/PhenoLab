<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObservationPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'observation_id' => $this->observation_id,
            'observation_plant' => $this->whenLoaded('observation', fn () => $this->observation->plant?->name),
            'observation_date' => $this->whenLoaded('observation', fn () => $this->observation->observation_date?->toDateString()),
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'title' => $this->title,
            'description' => $this->description,
            'photo_type' => $this->photo_type,
            'photographer' => $this->whenLoaded('photographer', fn () => [
                'id' => $this->photographer->id,
                'name' => $this->photographer->name,
            ]),
            'width' => $this->width,
            'height' => $this->height,
            'file_size' => $this->file_size,
            'display_order' => $this->display_order,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
