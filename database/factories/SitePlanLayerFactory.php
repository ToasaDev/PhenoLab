<?php

namespace Database\Factories;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class SitePlanLayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'name' => fake()->words(2, true),
            'start_date' => fake()->date(),
            'is_active' => true,
        ];
    }
}
