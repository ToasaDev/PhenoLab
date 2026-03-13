<?php

namespace Database\Factories;

use App\Models\PhenologicalStage;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ObservationFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-2 years', 'now');
        return [
            'observation_date' => $date->format('Y-m-d'),
            'plant_id' => Plant::factory(),
            'phenological_stage_id' => PhenologicalStage::factory(),
            'observer_id' => User::factory(),
            'confidence_level' => fake()->numberBetween(1, 5),
            'is_public' => true,
            'day_of_year' => (int) $date->format('z') + 1,
        ];
    }
}
