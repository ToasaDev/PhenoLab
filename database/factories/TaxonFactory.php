<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaxonFactory extends Factory
{
    public function definition(): array
    {
        $genus = fake()->word();
        $species = fake()->word();
        return [
            'taxon_id' => 'TX' . fake()->unique()->numberBetween(1000, 99999),
            'kingdom' => 'Plantae',
            'genus' => ucfirst($genus),
            'species' => $species,
            'binomial_name' => ucfirst($genus) . ' ' . $species,
            'family' => ucfirst(fake()->word()) . 'aceae',
        ];
    }
}
