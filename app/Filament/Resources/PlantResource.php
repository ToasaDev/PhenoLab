<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlantResource\Pages;
use App\Models\Plant;
use App\Models\PlantCultivationProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlantResource extends Resource
{
    protected static ?string $model = Plant::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Terrain';

    protected static ?string $navigationLabel = 'Plantes';

    protected static ?string $modelLabel = 'Plante';

    protected static ?string $pluralModelLabel = 'Plantes';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identification')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Nom')->required()->maxLength(255),
                    Forms\Components\Select::make('taxon_id')->label('Taxon')
                        ->relationship('taxon', 'binomial_name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('category_id')->label('Categorie')
                        ->relationship('category', 'name')
                        ->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')
                        ->relationship('site', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('owner_id')->label('Proprietaire')
                        ->relationship('owner', 'name'),
                    Forms\Components\Select::make('status')
                        ->label('Statut')
                        ->options([
                            'active' => 'Actif',
                            'dormant' => 'Dormant',
                            'dead' => 'Mort',
                            'removed' => 'Retire',
                        ])
                        ->default('active'),
                    Forms\Components\Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Caracteristiques')
                ->columns(3)
                ->schema([
                    Forms\Components\DatePicker::make('planting_date')->label('Date de plantation'),
                    Forms\Components\TextInput::make('age_years')->label('Age (ans)')->numeric(),
                    Forms\Components\Select::make('height_category')
                        ->label('Categorie hauteur')
                        ->options([
                            'small' => 'Petit (<1m)',
                            'medium' => 'Moyen (1-5m)',
                            'large' => 'Grand (5-15m)',
                            'very_large' => 'Tres grand (>15m)',
                        ]),
                    Forms\Components\TextInput::make('exact_height')->label('Hauteur exacte (m)')->numeric(),
                    Forms\Components\Select::make('health_status')
                        ->label('Etat de sante')
                        ->options([
                            'excellent' => 'Excellent',
                            'good' => 'Bon',
                            'fair' => 'Moyen',
                            'poor' => 'Mauvais',
                            'critical' => 'Critique',
                        ]),
                    Forms\Components\TextInput::make('clone_or_accession')->label('Clone/Accession')->maxLength(100),
                ]),

            Forms\Components\Section::make('Localisation GPS')
                ->columns(3)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('latitude')->numeric()->step(0.00000001),
                    Forms\Components\TextInput::make('longitude')->numeric()->step(0.00000001),
                    Forms\Components\TextInput::make('gps_accuracy')->label('Precision GPS (m)')->numeric(),
                ]),

            Forms\Components\Section::make('Deces / Remplacement')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\DatePicker::make('death_date')->label('Date de deces'),
                    Forms\Components\TextInput::make('death_cause')->label('Cause du deces')->maxLength(255),
                    Forms\Components\Textarea::make('death_notes')->label('Notes deces')->rows(2),
                    Forms\Components\Select::make('replaces_id')->label('Remplace')
                        ->relationship('replaces', 'name')
                        ->searchable(),
                ]),

            Forms\Components\Section::make('Notes')
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('notes')->label('Notes')->rows(3),
                    Forms\Components\Textarea::make('anecdotes')->label('Anecdotes')->rows(3),
                    Forms\Components\Textarea::make('ecological_notes')->label('Notes ecologiques')->rows(3),
                    Forms\Components\Textarea::make('care_notes')->label('Notes d\'entretien')->rows(3),
                ]),

            Forms\Components\Section::make('Conditions de culture')
                ->description('Recommandations horticoles (independantes des observations).')
                ->relationship('cultivationProfile')
                ->collapsed()
                ->schema([
                    Forms\Components\Tabs::make('cultivation_tabs')
                        ->columnSpanFull()
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Quand planter')->schema([
                                Forms\Components\CheckboxList::make('planting_months')
                                    ->label('Mois de plantation')
                                    ->options(self::monthOptions())->columns(4),
                                Forms\Components\CheckboxList::make('sowing_months')
                                    ->label('Mois de semis')
                                    ->options(self::monthOptions())->columns(4),
                                Forms\Components\CheckboxList::make('flowering_months')
                                    ->label('Mois de floraison')
                                    ->options(self::monthOptions())->columns(4),
                                Forms\Components\CheckboxList::make('harvest_months')
                                    ->label('Mois de recolte')
                                    ->options(self::monthOptions())->columns(4),
                            ]),
                            Forms\Components\Tabs\Tab::make('Ou cultiver')->columns(2)->schema([
                                Forms\Components\Select::make('exposure')->label('Exposition')
                                    ->options(PlantCultivationProfile::EXPOSURES),
                                Forms\Components\TextInput::make('hardiness_min')->label('Temperature min')->maxLength(20),
                                Forms\Components\TextInput::make('usda_zone')->label('Zone USDA')->maxLength(20),
                                Forms\Components\Select::make('suitable_environments')->label('Environnements adaptes')
                                    ->multiple()
                                    ->options(\App\Models\Site::ENVIRONMENT_TYPES ?? []),
                                Forms\Components\Select::make('soil_types')->label('Type(s) de sol')
                                    ->multiple()
                                    ->options(PlantCultivationProfile::SOIL_TYPES),
                                Forms\Components\TextInput::make('soil_ph')->label('pH du sol')->maxLength(30),
                                Forms\Components\Select::make('soil_drainage')->label('Drainage')
                                    ->options(PlantCultivationProfile::SOIL_DRAINAGE),
                                Forms\Components\Select::make('soil_fertility')->label('Fertilite')
                                    ->options(PlantCultivationProfile::SOIL_FERTILITY),
                                Forms\Components\TextInput::make('mature_height_min')->label('Hauteur min (m)')->numeric()->step(0.01),
                                Forms\Components\TextInput::make('mature_height_max')->label('Hauteur max (m)')->numeric()->step(0.01),
                                Forms\Components\TextInput::make('mature_spread_min')->label('Envergure min (m)')->numeric()->step(0.01),
                                Forms\Components\TextInput::make('mature_spread_max')->label('Envergure max (m)')->numeric()->step(0.01),
                            ]),
                            Forms\Components\Tabs\Tab::make('Soins')->columns(2)->schema([
                                Forms\Components\Select::make('watering_needs')->label('Besoins en eau')
                                    ->options(PlantCultivationProfile::WATERING_NEEDS),
                                Forms\Components\Textarea::make('watering_notes')->label('Notes arrosage')->rows(2),
                                Forms\Components\TextInput::make('fertilizing_frequency')->label('Frequence fertilisation')->maxLength(50),
                                Forms\Components\Textarea::make('fertilizing_notes')->label('Notes fertilisation')->rows(2),
                                Forms\Components\TextInput::make('pruning_period')->label('Periode de taille')->maxLength(100),
                                Forms\Components\Textarea::make('pruning_notes')->label('Notes taille')->rows(2),
                                Forms\Components\TextInput::make('mulching')->label('Paillage')->maxLength(50),
                                Forms\Components\TextInput::make('winter_protection')->label('Protection hivernale')->maxLength(100),
                                Forms\Components\Textarea::make('pest_susceptibility')->label('Sensibilite aux ravageurs')->rows(2),
                                Forms\Components\Textarea::make('disease_susceptibility')->label('Sensibilite aux maladies')->rows(2),
                                Forms\Components\TagsInput::make('companion_plants')->label('Plantes compagnes'),
                                Forms\Components\TagsInput::make('avoid_near')->label('A eviter a proximite'),
                                Forms\Components\TextInput::make('propagation_methods')->label('Methodes de propagation')->maxLength(200),
                                Forms\Components\Select::make('cultivation_difficulty')->label('Difficulte')
                                    ->options(PlantCultivationProfile::DIFFICULTIES),
                                Forms\Components\Select::make('usage_types')->label('Usages')
                                    ->multiple()
                                    ->options(PlantCultivationProfile::USAGE_TYPES),
                                Forms\Components\Toggle::make('is_edible')->label('Comestible'),
                                Forms\Components\Toggle::make('is_toxic')->label('Toxique'),
                            ]),
                            Forms\Components\Tabs\Tab::make('Notes & Source')->schema([
                                Forms\Components\Textarea::make('notes')->label('Notes libres')->rows(3),
                                Forms\Components\TextInput::make('source')->label('Source / reference')->maxLength(255),
                            ]),
                        ]),
                ]),

            Forms\Components\Toggle::make('is_private')->label('Prive'),
        ]);
    }

    protected static function monthOptions(): array
    {
        return [
            1 => 'Janv', 2 => 'Fev', 3 => 'Mars', 4 => 'Avr',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Aout',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('taxon.binomial_name')->label('Taxon')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Categorie')->sortable()->badge(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Statut')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'dormant' => 'warning',
                        'dead' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('health_status')->label('Sante')->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'excellent', 'good' => 'success',
                        'fair' => 'warning',
                        'poor', 'critical' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('observations_count')->label('Obs.')->counts('observations')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Proprietaire')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('site_id')->label('Site')->relationship('site', 'name'),
                Tables\Filters\SelectFilter::make('category_id')->label('Categorie')->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('status')->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'dormant' => 'Dormant',
                        'dead' => 'Mort',
                        'removed' => 'Retire',
                    ]),
                Tables\Filters\SelectFilter::make('health_status')->label('Sante')
                    ->options([
                        'excellent' => 'Excellent',
                        'good' => 'Bon',
                        'fair' => 'Moyen',
                        'poor' => 'Mauvais',
                        'critical' => 'Critique',
                    ]),
                Tables\Filters\TernaryFilter::make('is_private')->label('Prive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlants::route('/'),
            'create' => Pages\CreatePlant::route('/create'),
            'edit' => Pages\EditPlant::route('/{record}/edit'),
        ];
    }
}
