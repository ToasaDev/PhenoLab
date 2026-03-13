<?php

namespace Database\Seeders;

use App\Models\PhenologicalStage;
use Illuminate\Database\Seeder;

class PhenologicalStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['stage_code' => '07', 'stage_description' => "Début d'ouverture des bourgeons", 'main_event_code' => 1, 'main_event_description' => 'Débourrement / Feuillaison'],
            ['stage_code' => '10', 'stage_description' => 'Premières feuilles séparées (souris)', 'main_event_code' => 1, 'main_event_description' => 'Débourrement / Feuillaison'],
            ['stage_code' => '11', 'stage_description' => 'Premières feuilles étalées', 'main_event_code' => 1, 'main_event_description' => 'Débourrement / Feuillaison'],
            ['stage_code' => '15', 'stage_description' => 'Feuillage complet', 'main_event_code' => 1, 'main_event_description' => 'Débourrement / Feuillaison'],
            ['stage_code' => '60', 'stage_description' => 'Premières fleurs ouvertes', 'main_event_code' => 2, 'main_event_description' => 'Floraison'],
            ['stage_code' => '61', 'stage_description' => 'Début de floraison (10% de fleurs ouvertes)', 'main_event_code' => 2, 'main_event_description' => 'Floraison'],
            ['stage_code' => '65', 'stage_description' => 'Pleine floraison (50% de fleurs ouvertes)', 'main_event_code' => 2, 'main_event_description' => 'Floraison'],
            ['stage_code' => '80', 'stage_description' => 'Premiers fruits mûrs', 'main_event_code' => 3, 'main_event_description' => 'Fructification'],
            ['stage_code' => '85', 'stage_description' => '50% des fruits mûrs', 'main_event_code' => 3, 'main_event_description' => 'Fructification'],
            ['stage_code' => '87', 'stage_description' => 'Tous les fruits mûrs', 'main_event_code' => 3, 'main_event_description' => 'Fructification'],
            ['stage_code' => '89', 'stage_description' => 'Fin de fructification', 'main_event_code' => 3, 'main_event_description' => 'Fructification'],
            ['stage_code' => '91', 'stage_description' => 'Début de coloration automnale des feuilles', 'main_event_code' => 4, 'main_event_description' => 'Sénescence / Chute des feuilles'],
            ['stage_code' => '93', 'stage_description' => 'Début de chute des feuilles', 'main_event_code' => 4, 'main_event_description' => 'Sénescence / Chute des feuilles'],
            ['stage_code' => '95', 'stage_description' => '50% des feuilles tombées', 'main_event_code' => 4, 'main_event_description' => 'Sénescence / Chute des feuilles'],
            ['stage_code' => 'PA01', 'stage_description' => 'Première apparition', 'main_event_code' => 5, 'main_event_description' => 'Première apparition'],
            ['stage_code' => 'DA01', 'stage_description' => 'Dernière apparition', 'main_event_code' => 6, 'main_event_description' => 'Dernière apparition'],
        ];

        foreach ($stages as $stage) {
            PhenologicalStage::updateOrCreate(
                ['stage_code' => $stage['stage_code']],
                array_merge($stage, ['phenological_scale' => 'BBCH Tela Botanica'])
            );
        }
    }
}
