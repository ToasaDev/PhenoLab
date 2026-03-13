<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlantResource\Pages;
use App\Models\Plant;
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

            Forms\Components\Toggle::make('is_private')->label('Prive'),
        ]);
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
