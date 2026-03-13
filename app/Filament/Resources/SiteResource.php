<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Terrain';

    protected static ?string $navigationLabel = 'Sites';

    protected static ?string $modelLabel = 'Site';

    protected static ?string $pluralModelLabel = 'Sites';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations generales')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Nom')->required()->maxLength(255),
                    Forms\Components\Select::make('owner_id')->label('Proprietaire')->relationship('owner', 'name'),
                    Forms\Components\Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                    Forms\Components\Select::make('environment')
                        ->label('Environnement')
                        ->options([
                            'urban' => 'Urbain',
                            'suburban' => 'Periurbain',
                            'rural' => 'Rural',
                            'forest' => 'Foret',
                            'mountain' => 'Montagne',
                            'coastal' => 'Littoral',
                            'wetland' => 'Zone humide',
                        ]),
                    Forms\Components\Toggle::make('is_private')->label('Prive'),
                ]),

            Forms\Components\Section::make('Localisation')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('latitude')->numeric()->step(0.000001),
                    Forms\Components\TextInput::make('longitude')->numeric()->step(0.000001),
                    Forms\Components\TextInput::make('altitude')->label('Altitude (m)')->numeric(),
                ]),

            Forms\Components\Section::make('Caracteristiques')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('soil_type')->label('Type de sol')->maxLength(100),
                    Forms\Components\TextInput::make('exposure')->label('Exposition')->maxLength(50),
                    Forms\Components\TextInput::make('slope')->label('Pente')->maxLength(50),
                    Forms\Components\TextInput::make('climate_zone')->label('Zone climatique')->maxLength(100),
                ]),

            Forms\Components\Section::make('Plan du site')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\FileUpload::make('site_plan_image')->label('Image du plan')->image(),
                    Forms\Components\TextInput::make('plan_width_meters')->label('Largeur (m)')->numeric(),
                    Forms\Components\TextInput::make('plan_height_meters')->label('Hauteur (m)')->numeric(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Proprietaire')->sortable(),
                Tables\Columns\TextColumn::make('environment')->label('Environnement')->badge()->sortable(),
                Tables\Columns\TextColumn::make('altitude')->label('Altitude')->suffix(' m')->sortable(),
                Tables\Columns\TextColumn::make('plants_count')->label('Plantes')->counts('plants')->sortable(),
                Tables\Columns\IconColumn::make('is_private')->label('Prive')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('environment')->label('Environnement')
                    ->options([
                        'urban' => 'Urbain',
                        'suburban' => 'Periurbain',
                        'rural' => 'Rural',
                        'forest' => 'Foret',
                        'mountain' => 'Montagne',
                    ]),
                Tables\Filters\TernaryFilter::make('is_private')->label('Prive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}
