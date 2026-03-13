<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plant_id' => $this->plant_id,
            'plant_name' => $this->whenLoaded('plant', fn () => $this->plant->name),
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'title' => $this->title,
            'description' => $this->description,
            'photo_type' => $this->photo_type,
            'photographer' => $this->whenLoaded('photographer', fn () => [
                'id' => $this->photographer->id,
                'name' => $this->photographer->name,
            ]),
            'taken_date' => $this->taken_date?->toDateString(),
            'camera_model' => $this->camera_model,
            'focal_length' => $this->focal_length,
            'aperture' => $this->aperture,
            'iso' => $this->iso,
            'width' => $this->width,
            'height' => $this->height,
            'file_size' => $this->file_size,
            'is_main_photo' => $this->is_main_photo,
            'display_order' => $this->display_order,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
