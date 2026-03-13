<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Site;
use App\Models\Taxon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'taxon_id' => Taxon::factory(),
            'category_id' => Category::factory(),
            'site_id' => Site::factory(),
            'owner_id' => User::factory(),
            'health_status' => 'good',
            'status' => 'alive',
            'is_private' => false,
        ];
    }
}
