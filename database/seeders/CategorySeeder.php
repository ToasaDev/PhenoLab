<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Arbres', 'category_type' => 'trees', 'icon' => 'fa-tree', 'description' => 'Arbres de toutes espèces'],
            ['name' => 'Arbustes', 'category_type' => 'shrubs', 'icon' => 'fa-leaf', 'description' => 'Arbustes et buissons'],
            ['name' => 'Plantes herbacées', 'category_type' => 'plants', 'icon' => 'fa-seedling', 'description' => 'Plantes herbacées, fleurs et graminées'],
            ['name' => 'Animaux', 'category_type' => 'animals', 'icon' => 'fa-paw', 'description' => 'Animaux observables'],
            ['name' => 'Insectes', 'category_type' => 'insects', 'icon' => 'fa-bug', 'description' => 'Insectes et pollinisateurs'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['name' => $cat['name']],
                $cat
            );
        }
    }
}
