<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlantPhotoResource\Pages;
use App\Models\PlantPhoto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlantPhotoResource extends Resource
{
    protected static ?string $model = PlantPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationGroup = 'Photos';

    protected static ?string $navigationLabel = 'Photos de plantes';

    protected static ?string $modelLabel = 'Photo de plante';

    protected static ?string $pluralModelLabel = 'Photos de plantes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Photo')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('plant_id')->label('Plante')
                        ->relationship('plant', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\FileUpload::make('image')->label('Image')->image()->required(),
                    Forms\Components\TextInput::make('title')->label('Titre')->maxLength(255),
                    Forms\Components\Textarea::make('description')->label('Description')->rows(2),
                    Forms\Components\Select::make('photo_type')
                        ->label('Type')
                        ->options([
                            'general' => 'General',
                            'foliage' => 'Feuillage',
                            'flower' => 'Fleur',
                            'fruit' => 'Fruit',
                            'bark' => 'Ecorce',
                            'root' => 'Racine',
                            'habit' => 'Port',
                        ]),
                    Forms\Components\Select::make('photographer_id')->label('Photographe')
                        ->relationship('photographer', 'name'),
                    Forms\Components\TextInput::make('display_order')->label('Ordre')->numeric()->default(0),
                    Forms\Components\Toggle::make('is_main_photo')->label('Photo principale'),
                    Forms\Components\Toggle::make('is_public')->label('Publique')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Image')->height(50),
                Tables\Columns\TextColumn::make('plant.name')->label('Plante')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Titre')->searchable(),
                Tables\Columns\TextColumn::make('photo_type')->label('Type')->badge(),
                Tables\Columns\IconColumn::make('is_main_photo')->label('Principale')->boolean(),
                Tables\Columns\IconColumn::make('is_public')->label('Publique')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('photo_type')->label('Type')
                    ->options([
                        'general' => 'General',
                        'foliage' => 'Feuillage',
                        'flower' => 'Fleur',
                        'fruit' => 'Fruit',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlantPhotos::route('/'),
            'create' => Pages\CreatePlantPhoto::route('/create'),
            'edit' => Pages\EditPlantPhoto::route('/{record}/edit'),
        ];
    }
}
