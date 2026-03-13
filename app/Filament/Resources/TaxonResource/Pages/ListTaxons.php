<?php

namespace App\Filament\Resources\TaxonResource\Pages;

use App\Filament\Resources\TaxonResource;
use App\Services\GbifImportService;
use App\Services\GbifService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTaxons extends ListRecords
{
    protected static string $resource = TaxonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('syncGbif')
                ->label('Sync GBIF')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('sync_mode')
                        ->label('Mode')
                        ->options([
                            'backbone_match' => 'Correspondance exacte (Backbone)',
                            'search' => 'Recherche (plusieurs resultats)',
                        ])
                        ->default('backbone_match')
                        ->required(),
                    Forms\Components\TextInput::make('search_query')
                        ->label('Nom scientifique')
                        ->required()
                        ->minLength(2)
                        ->placeholder('Ex: Quercus robur'),
                    Forms\Components\TextInput::make('import_limit')
                        ->label('Limite (mode recherche)')
                        ->numeric()
                        ->default(20)
                        ->minValue(1)
                        ->maxValue(500),
                    Forms\Components\Toggle::make('strict_mode')
                        ->label('Mode strict')
                        ->default(false),
                    Forms\Components\Toggle::make('fetch_vernacular')
                        ->label('Recuperer noms vernaculaires')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $importService = app(GbifImportService::class);
                    $gbifService = app(GbifService::class);

                    $synced = 0;
                    $errors = [];

                    if ($data['sync_mode'] === 'backbone_match') {
                        $result = $importService->syncByScientificName(
                            $data['search_query'],
                            $data['strict_mode'] ?? false,
                            $data['fetch_vernacular'] ?? true,
                        );

                        if ($result['success']) {
                            $synced = 1;
                        } else {
                            $errors[] = $result['message'];
                        }
                    } else {
                        $searchResults = $gbifService->searchTaxa($data['search_query'], $data['import_limit'] ?? 20);

                        foreach ($searchResults['results'] ?? [] as $item) {
                            $rank = strtoupper($item['rank'] ?? '');
                            if (! in_array($rank, GbifImportService::ALLOWED_RANKS)) {
                                continue;
                            }

                            $sciName = $item['canonicalName'] ?? $item['scientificName'] ?? '';
                            if (! $sciName) {
                                continue;
                            }

                            try {
                                $result = $importService->syncByScientificName($sciName, false, $data['fetch_vernacular'] ?? true);
                                $result['success'] ? $synced++ : $errors[] = "{$sciName}: {$result['message']}";
                            } catch (\Exception $e) {
                                $errors[] = "{$sciName}: {$e->getMessage()}";
                            }
                        }
                    }

                    $msg = "{$synced} taxon(s) synchronise(s)";
                    if (count($errors) > 0) {
                        $msg .= ", " . count($errors) . " erreur(s)";
                    }

                    Notification::make()
                        ->title($msg)
                        ->body(count($errors) > 0 ? implode("\n", array_slice($errors, 0, 5)) : null)
                        ->success()
                        ->send();
                }),

            Actions\Action::make('importFamily')
                ->label('Importer famille')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('family_name')
                        ->label('Nom de la famille')
                        ->required()
                        ->minLength(2)
                        ->placeholder('Ex: Fagaceae'),
                    Forms\Components\Toggle::make('accepted_only')
                        ->label('Taxons acceptes uniquement')
                        ->default(true),
                    Forms\Components\TextInput::make('import_limit')
                        ->label('Limite')
                        ->numeric()
                        ->default(100)
                        ->minValue(1)
                        ->maxValue(5000),
                    Forms\Components\Toggle::make('dry_run')
                        ->label('Simulation (dry run)')
                        ->default(false)
                        ->helperText('Tester sans rien creer en base'),
                ])
                ->action(function (array $data) {
                    $importService = app(GbifImportService::class);

                    $result = $importService->importFamilySpecies(
                        $data['family_name'],
                        $data['accepted_only'] ?? true,
                        $data['import_limit'] ?? 100,
                        $data['dry_run'] ?? false,
                    );

                    $r = $result['results'];
                    $prefix = ($data['dry_run'] ?? false) ? '[SIMULATION] ' : '';

                    Notification::make()
                        ->title("{$prefix}Import famille {$data['family_name']}")
                        ->body("Crees: {$r['created']}, Mis a jour: {$r['updated']}, Ignores: {$r['skipped']}, Erreurs: {$r['errors']}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
