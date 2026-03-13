<?php

namespace App\Console\Commands;

use App\Models\PhenologicalStage;
use Illuminate\Console\Command;

class SyncPhenologicalStagesCommand extends Command
{
    protected $signature = 'stages:sync {--dry-run} {--force} {--event=}';
    protected $description = 'Synchroniser les stades phénologiques BBCH';

    private array $stages = [
        // Phase 0 : Germination / Débourrement (événement principal 1)
        ['stage_code' => '01', 'stage_description' => 'Début du gonflement des bourgeons', 'main_event_code' => 1, 'main_event_description' => 'Développement des feuilles (pousse principale)'],
        ['stage_code' => '03', 'stage_description' => 'Fin du gonflement des bourgeons', 'main_event_code' => 1, 'main_event_description' => 'Développement des feuilles (pousse principale)'],
        ['stage_code' => '07', 'stage_description' => 'Début du débourrement : les pointes des feuilles sont visibles', 'main_event_code' => 1, 'main_event_description' => 'Développement des feuilles (pousse principale)'],
        ['stage_code' => '09', 'stage_description' => 'Fin du débourrement : les feuilles sont vertes, premières feuilles séparées', 'main_event_code' => 1, 'main_event_description' => 'Développement des feuilles (pousse principale)'],
        // Phase 1 : Développement des feuilles (événement principal 1)
        ['stage_code' => '11', 'stage_description' => 'Environ 10% des feuilles épanouies', 'main_event_code' => 1, 'main_event_description' => 'Développement des feuilles (pousse principale)'],
        ['stage_code' => '15', 'stage_description' => 'Environ 50% des feuilles épanouies', 'main_event_code' => 1, 'main_event_description' => 'Développement des feuilles (pousse principale)'],
        ['stage_code' => '19', 'stage_description' => 'Toutes les feuilles épanouies', 'main_event_code' => 1, 'main_event_description' => 'Développement des feuilles (pousse principale)'],
        // Phase 2 : Formation des pousses latérales (événement principal 2)
        ['stage_code' => '21', 'stage_description' => 'Début de formation des pousses latérales', 'main_event_code' => 2, 'main_event_description' => 'Formation des pousses latérales'],
        ['stage_code' => '25', 'stage_description' => 'Formation de pousses latérales bien développées', 'main_event_code' => 2, 'main_event_description' => 'Formation des pousses latérales'],
        ['stage_code' => '29', 'stage_description' => 'Fin de formation des pousses latérales', 'main_event_code' => 2, 'main_event_description' => 'Formation des pousses latérales'],
        // Phase 3 : Développement de la tige (événement principal 3)
        ['stage_code' => '31', 'stage_description' => "Début de l'allongement de la pousse", 'main_event_code' => 3, 'main_event_description' => 'Développement de la tige/allongement de la pousse'],
        ['stage_code' => '32', 'stage_description' => "20% de l'allongement final de la pousse atteint", 'main_event_code' => 3, 'main_event_description' => 'Développement de la tige/allongement de la pousse'],
        ['stage_code' => '39', 'stage_description' => "Fin de l'allongement de la pousse", 'main_event_code' => 3, 'main_event_description' => 'Développement de la tige/allongement de la pousse'],
        // Phase 4 : Développement des organes reproducteurs (événement principal 4)
        ['stage_code' => '41', 'stage_description' => 'Développement des organes reproducteurs', 'main_event_code' => 4, 'main_event_description' => 'Développement des organes reproducteurs'],
        // Phase 5 : Émergence de l'inflorescence (événement principal 5)
        ['stage_code' => '51', 'stage_description' => "Début de l'émergence de l'inflorescence", 'main_event_code' => 5, 'main_event_description' => "Épiaison/émergence de l'inflorescence"],
        ['stage_code' => '55', 'stage_description' => 'Inflorescence émergée, boutons floraux visibles', 'main_event_code' => 5, 'main_event_description' => "Épiaison/émergence de l'inflorescence"],
        ['stage_code' => '59', 'stage_description' => 'Inflorescence complètement développée', 'main_event_code' => 5, 'main_event_description' => "Épiaison/émergence de l'inflorescence"],
        // Phase 6 : Floraison (événement principal 6)
        ['stage_code' => '60', 'stage_description' => 'Premières fleurs ouvertes', 'main_event_code' => 6, 'main_event_description' => 'Floraison'],
        ['stage_code' => '61', 'stage_description' => 'Environ 10% des fleurs épanouies', 'main_event_code' => 6, 'main_event_description' => 'Floraison'],
        ['stage_code' => '65', 'stage_description' => 'Pleine floraison : 50% des fleurs épanouies', 'main_event_code' => 6, 'main_event_description' => 'Floraison'],
        ['stage_code' => '67', 'stage_description' => 'Floraison déclinante', 'main_event_code' => 6, 'main_event_description' => 'Floraison'],
        ['stage_code' => '69', 'stage_description' => 'Fin de floraison : tous les pétales tombés', 'main_event_code' => 6, 'main_event_description' => 'Floraison'],
        // Phase 7 : Formation des fruits (événement principal 7)
        ['stage_code' => '71', 'stage_description' => 'Début de formation des fruits', 'main_event_code' => 7, 'main_event_description' => 'Fructification'],
        ['stage_code' => '75', 'stage_description' => 'Fruits à environ 50% de leur taille finale', 'main_event_code' => 7, 'main_event_description' => 'Fructification'],
        ['stage_code' => '79', 'stage_description' => 'Fruits ont atteint leur taille finale', 'main_event_code' => 7, 'main_event_description' => 'Fructification'],
        // Phase 8 : Maturité des fruits et graines (événement principal 8)
        ['stage_code' => '81', 'stage_description' => 'Début de maturation : premiers changements de couleur', 'main_event_code' => 8, 'main_event_description' => 'Maturation des fruits et graines'],
        ['stage_code' => '85', 'stage_description' => 'Maturation avancée des fruits', 'main_event_code' => 8, 'main_event_description' => 'Maturation des fruits et graines'],
        ['stage_code' => '87', 'stage_description' => 'Fruits mûrs pour la récolte', 'main_event_code' => 8, 'main_event_description' => 'Maturation des fruits et graines'],
        ['stage_code' => '89', 'stage_description' => 'Fruits surmaturisés', 'main_event_code' => 8, 'main_event_description' => 'Maturation des fruits et graines'],
        // Phase 9 : Sénescence et dormance (événement principal 9)
        ['stage_code' => '91', 'stage_description' => 'Début de la sénescence : changement de couleur des feuilles', 'main_event_code' => 9, 'main_event_description' => 'Sénescence et dormance'],
        ['stage_code' => '92', 'stage_description' => 'Environ 25% des feuilles colorées', 'main_event_code' => 9, 'main_event_description' => 'Sénescence et dormance'],
        ['stage_code' => '93', 'stage_description' => 'Environ 50% des feuilles colorées', 'main_event_code' => 9, 'main_event_description' => 'Sénescence et dormance'],
        ['stage_code' => '95', 'stage_description' => 'Environ 75% des feuilles colorées', 'main_event_code' => 9, 'main_event_description' => 'Sénescence et dormance'],
        ['stage_code' => '97', 'stage_description' => 'Chute des feuilles', 'main_event_code' => 9, 'main_event_description' => 'Sénescence et dormance'],
        ['stage_code' => '99', 'stage_description' => 'Dormance complète', 'main_event_code' => 9, 'main_event_description' => 'Sénescence et dormance'],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $eventFilter = $this->option('event');

        $this->info($dryRun ? 'Mode simulation (dry-run)' : 'Synchronisation des stades phénologiques BBCH');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($this->stages as $stageData) {
            if ($eventFilter !== null && (int) $eventFilter !== $stageData['main_event_code']) {
                continue;
            }

            $stageData['phenological_scale'] = 'BBCH Tela Botanica';

            $existing = PhenologicalStage::where('stage_code', $stageData['stage_code'])->first();

            if ($existing) {
                if ($force) {
                    if (!$dryRun) {
                        $existing->update($stageData);
                    }
                    $updated++;
                    $this->line("  Mis à jour : {$stageData['stage_code']} - {$stageData['stage_description']}");
                } else {
                    $skipped++;
                    $this->line("  Ignoré (existe) : {$stageData['stage_code']}");
                }
            } else {
                if (!$dryRun) {
                    PhenologicalStage::create($stageData);
                }
                $created++;
                $this->line("  Créé : {$stageData['stage_code']} - {$stageData['stage_description']}");
            }
        }

        $this->newLine();
        $this->info($dryRun ? 'Résultat (simulation) :' : 'Résultat :');
        $this->table(
            ['Action', 'Nombre'],
            [
                ['Créés', $created],
                ['Mis à jour', $updated],
                ['Ignorés', $skipped],
            ]
        );

        return Command::SUCCESS;
    }
}
