<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxonResource\Pages;
use App\Models\Taxon;
use App\Services\GbifImportService;
use App\Services\GbifService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class TaxonResource extends Resource
{
    protected static ?string $model = Taxon::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Taxonomie';

    protected static ?string $navigationLabel = 'Taxons';

    protected static ?string $modelLabel = 'Taxon';

    protected static ?string $pluralModelLabel = 'Taxons';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identification')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('taxon_id')
                        ->label('ID Taxon')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('binomial_name')
                        ->label('Nom binomial')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('genus')
                        ->label('Genre')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('species')
                        ->label('Espece')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('subspecies')
                        ->label('Sous-espece')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('variety')
                        ->label('Variete')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('cultivar')
                        ->label('Cultivar')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('author')
                        ->label('Auteur')
                        ->maxLength(1000),
                    Forms\Components\TextInput::make('publication_year')
                        ->label('Annee de publication')
                        ->numeric(),
                ]),

            Forms\Components\Section::make('Classification')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('kingdom')->label('Regne')->maxLength(100)->default('Plantae'),
                    Forms\Components\TextInput::make('phylum')->label('Phylum')->maxLength(100),
                    Forms\Components\TextInput::make('class_name')->label('Classe')->maxLength(100),
                    Forms\Components\TextInput::make('order')->label('Ordre')->maxLength(100),
                    Forms\Components\TextInput::make('family')->label('Famille')->maxLength(100),
                ]),

            Forms\Components\Section::make('Noms communs')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('common_name_fr')->label('Nom commun (FR)')->maxLength(1000),
                    Forms\Components\TextInput::make('common_name_en')->label('Nom commun (EN)')->maxLength(1000),
                    Forms\Components\TextInput::make('common_name_it')->label('Nom commun (IT)')->maxLength(1000),
                ]),

            Forms\Components\Section::make('GBIF')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('gbif_id')->label('GBIF ID')->numeric(),
                    Forms\Components\TextInput::make('gbif_status')->label('Statut GBIF')->maxLength(50),
                    Forms\Components\TextInput::make('gbif_rank')->label('Rang GBIF')->maxLength(50),
                    Forms\Components\TextInput::make('gbif_canonical_name')->label('Nom canonique GBIF')->maxLength(1000),
                    Forms\Components\DateTimePicker::make('gbif_synced_at')->label('Derniere synchro GBIF')->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('binomial_name')
                    ->label('Nom binomial')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('common_name_fr')
                    ->label('Nom FR')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('family')
                    ->label('Famille')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('genus')
                    ->label('Genre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plants_count')
                    ->label('Plantes')
                    ->counts('plants')
                    ->sortable(),
                Tables\Columns\IconColumn::make('gbif_id')
                    ->label('GBIF')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn (Taxon $record) => (bool) $record->gbif_id),
                Tables\Columns\TextColumn::make('gbif_synced_at')
                    ->label('Synchro GBIF')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('family')
                    ->label('Famille')
                    ->options(fn () => Taxon::whereNotNull('family')->distinct()->orderBy('family')->pluck('family', 'family')),
                Tables\Filters\SelectFilter::make('kingdom')
                    ->label('Regne')
                    ->options(fn () => Taxon::whereNotNull('kingdom')->distinct()->pluck('kingdom', 'kingdom')),
                Tables\Filters\TernaryFilter::make('has_gbif')
                    ->label('Lie a GBIF')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('gbif_id'),
                        false: fn ($query) => $query->whereNull('gbif_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('syncFromGbif')
                    ->label('Sync GBIF')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Synchroniser depuis GBIF')
                    ->modalDescription(fn (Taxon $record) => "Synchroniser {$record->binomial_name} depuis GBIF ?")
                    ->action(function (Taxon $record) {
                        $importService = app(GbifImportService::class);
                        $gbifService = app(GbifService::class);

                        if ($record->gbif_id) {
                            $gbifDetails = $gbifService->getTaxon($record->gbif_id);
                            if ($gbifDetails) {
                                $importService->upsertFromGbif($gbifDetails, true);
                                Notification::make()->title('Synchronise depuis GBIF (ID)')->success()->send();
                                return;
                            }
                        }

                        $name = $record->binomial_name ?: "{$record->genus} {$record->species}";
                        $result = $importService->syncByScientificName($name, false, true);

                        if ($result['success']) {
                            Notification::make()->title($result['message'])->success()->send();
                        } else {
                            Notification::make()->title($result['message'])->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkSyncGbif')
                        ->label('Sync GBIF')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Synchroniser les taxons selectionnes depuis GBIF')
                        ->action(function (Collection $records) {
                            $importService = app(GbifImportService::class);
                            $synced = 0;
                            $errors = 0;

                            foreach ($records as $taxon) {
                                $name = $taxon->binomial_name ?: "{$taxon->genus} {$taxon->species}";
                                try {
                                    $result = $importService->syncByScientificName($name, false, true);
                                    $result['success'] ? $synced++ : $errors++;
                                } catch (\Exception $e) {
                                    $errors++;
                                }
                            }

                            Notification::make()
                                ->title("{$synced} synchronise(s), {$errors} erreur(s)")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('binomial_name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxons::route('/'),
            'create' => Pages\CreateTaxon::route('/create'),
            'edit' => Pages\EditTaxon::route('/{record}/edit'),
        ];
    }
}
