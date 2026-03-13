<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'latitude' => fake()->latitude(42, 51),
            'longitude' => fake()->longitude(-5, 10),
            'altitude' => fake()->optional()->numberBetween(0, 2500),
            'environment' => fake()->randomElement(['urban', 'suburban', 'rural', 'forest', 'garden', 'natural', 'agricultural']),
            'is_private' => false,
            'owner_id' => User::factory(),
        ];
    }
}
