<?php

namespace Database\Factories;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlantPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plant_id' => Plant::factory(),
            'image' => 'photos/plants/test.jpg',
            'photo_type' => 'general',
            'photographer_id' => User::factory(),
            'is_main_photo' => false,
            'is_public' => true,
        ];
    }
}
