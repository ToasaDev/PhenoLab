<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ObservationPhotoResource\Pages;
use App\Models\ObservationPhoto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ObservationPhotoResource extends Resource
{
    protected static ?string $model = ObservationPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Photos';

    protected static ?string $navigationLabel = 'Photos d\'observations';

    protected static ?string $modelLabel = 'Photo d\'observation';

    protected static ?string $pluralModelLabel = 'Photos d\'observations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('observation_id')->label('Observation')
                ->relationship('observation', 'id')
                ->required()
                ->searchable(),
            Forms\Components\FileUpload::make('image')->label('Image')->image()->required(),
            Forms\Components\TextInput::make('title')->label('Titre')->maxLength(255),
            Forms\Components\Textarea::make('description')->label('Description')->rows(2),
            Forms\Components\Select::make('photo_type')
                ->label('Type')
                ->options([
                    'general' => 'General',
                    'detail' => 'Detail',
                    'context' => 'Contexte',
                ]),
            Forms\Components\Select::make('photographer_id')->label('Photographe')
                ->relationship('photographer', 'name'),
            Forms\Components\TextInput::make('display_order')->label('Ordre')->numeric()->default(0),
            Forms\Components\Toggle::make('is_public')->label('Publique')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Image')->height(50),
                Tables\Columns\TextColumn::make('observation.id')->label('Obs. ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Titre')->searchable(),
                Tables\Columns\TextColumn::make('photo_type')->label('Type')->badge(),
                Tables\Columns\IconColumn::make('is_public')->label('Publique')->boolean(),
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
            'index' => Pages\ListObservationPhotos::route('/'),
            'create' => Pages\CreateObservationPhoto::route('/create'),
            'edit' => Pages\EditObservationPhoto::route('/{record}/edit'),
        ];
    }
}
