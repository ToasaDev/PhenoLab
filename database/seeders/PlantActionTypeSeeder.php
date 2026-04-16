<?php

namespace Database\Seeders;

use App\Models\PlantActionType;
use Illuminate\Database\Seeder;

class PlantActionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Taille',                 'slug' => 'taille',              'category' => 'maintenance',   'icon' => 'fa-cut',              'color' => 'bg-success',  'applies_to' => 'all',  'sort_order' => 1],
            ['name' => 'Fertilisation',          'slug' => 'fertilisation',       'category' => 'fertilization', 'icon' => 'fa-flask',            'color' => 'bg-warning',  'applies_to' => 'all',  'sort_order' => 2],
            ['name' => 'Arrosage',               'slug' => 'arrosage',            'category' => 'irrigation',    'icon' => 'fa-tint',             'color' => 'bg-info',     'applies_to' => 'all',  'sort_order' => 3],
            ['name' => 'Paillage',               'slug' => 'paillage',            'category' => 'maintenance',   'icon' => 'fa-layer-group',      'color' => 'bg-secondary','applies_to' => 'all',  'sort_order' => 4],
            ['name' => 'Traitement phytosanitaire','slug' => 'traitement',         'category' => 'treatment',    'icon' => 'fa-spray-can',        'color' => 'bg-danger',   'applies_to' => 'all',  'sort_order' => 5],
            ['name' => 'Greffe',                 'slug' => 'greffe',              'category' => 'planting',      'icon' => 'fa-code-branch',      'color' => 'bg-primary',  'applies_to' => 'tree', 'sort_order' => 6],
            ['name' => 'Récolte',                'slug' => 'recolte',             'category' => 'harvest',       'icon' => 'fa-apple-alt',        'color' => 'bg-success',  'applies_to' => 'all',  'sort_order' => 7],
            ['name' => 'Plantation',             'slug' => 'plantation',          'category' => 'planting',      'icon' => 'fa-seedling',         'color' => 'bg-success',  'applies_to' => 'all',  'sort_order' => 8],
            ['name' => 'Transplantation',        'slug' => 'transplantation',     'category' => 'planting',      'icon' => 'fa-exchange-alt',     'color' => 'bg-info',     'applies_to' => 'all',  'sort_order' => 9],
            ['name' => 'Désherbage',             'slug' => 'desherbage',          'category' => 'maintenance',   'icon' => 'fa-broom',            'color' => 'bg-warning',  'applies_to' => 'all',  'sort_order' => 10],
            ['name' => 'Lutte ravageurs',        'slug' => 'lutte-ravageurs',     'category' => 'treatment',     'icon' => 'fa-bug',              'color' => 'bg-danger',   'applies_to' => 'all',  'sort_order' => 11],
            ['name' => 'Lutte maladies',         'slug' => 'lutte-maladies',      'category' => 'treatment',     'icon' => 'fa-virus',            'color' => 'bg-danger',   'applies_to' => 'all',  'sort_order' => 12],
            ['name' => 'Protection hivernale',   'slug' => 'protection-hivernale','category' => 'protection',    'icon' => 'fa-snowflake',        'color' => 'bg-info',     'applies_to' => 'all',  'sort_order' => 13],
            ['name' => 'Tuteurage',              'slug' => 'tuteurage',           'category' => 'maintenance',   'icon' => 'fa-arrows-alt-v',     'color' => 'bg-secondary','applies_to' => 'all',  'sort_order' => 14],
            ['name' => 'Amendement du sol',      'slug' => 'amendement-sol',      'category' => 'fertilization', 'icon' => 'fa-mountain',         'color' => 'bg-warning',  'applies_to' => 'all',  'sort_order' => 15],
            ['name' => 'Suppression bois mort',  'slug' => 'bois-mort',           'category' => 'maintenance',   'icon' => 'fa-tree',             'color' => 'bg-dark',     'applies_to' => 'tree', 'sort_order' => 16],
            ['name' => 'Palissage',              'slug' => 'palissage',           'category' => 'maintenance',   'icon' => 'fa-grip-lines',       'color' => 'bg-secondary','applies_to' => 'all',  'sort_order' => 17],
            ['name' => 'Semis',                  'slug' => 'semis',               'category' => 'planting',      'icon' => 'fa-hand-holding-seedling','color' => 'bg-success','applies_to' => 'all','sort_order' => 18],
            ['name' => 'Tonte autour',           'slug' => 'tonte-autour',        'category' => 'maintenance',   'icon' => 'fa-fan',              'color' => 'bg-success',  'applies_to' => 'all',  'sort_order' => 19],
            ['name' => 'Installation support',   'slug' => 'installation-support','category' => 'maintenance',   'icon' => 'fa-tools',            'color' => 'bg-secondary','applies_to' => 'all',  'sort_order' => 20],
            ['name' => 'Autre',                  'slug' => 'autre',               'category' => 'other',         'icon' => 'fa-ellipsis-h',       'color' => 'bg-light',    'applies_to' => 'all',  'sort_order' => 99],
        ];

        foreach ($types as $type) {
            PlantActionType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
