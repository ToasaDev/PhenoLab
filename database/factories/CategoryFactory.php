<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'category_type' => fake()->randomElement(['trees', 'shrubs', 'plants', 'animals', 'insects']),
            'icon' => 'fa-tree',
        ];
    }
}
