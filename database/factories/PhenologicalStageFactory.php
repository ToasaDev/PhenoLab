<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PhenologicalStageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stage_code' => fake()->unique()->numerify('##'),
            'stage_description' => fake()->sentence(),
            'main_event_code' => fake()->numberBetween(1, 6),
            'main_event_description' => fake()->words(3, true),
            'phenological_scale' => 'BBCH Tela Botanica',
        ];
    }
}
