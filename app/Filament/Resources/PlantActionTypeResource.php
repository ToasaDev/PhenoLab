<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlantActionTypeResource\Pages;
use App\Models\PlantActionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlantActionTypeResource extends Resource
{
    protected static ?string $model = PlantActionType::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Interventions';

    protected static ?string $navigationLabel = "Types d'actions";

    protected static ?string $modelLabel = "Type d'action";

    protected static ?string $pluralModelLabel = "Types d'actions";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->maxLength(100)
                ->helperText('Généré automatiquement si vide'),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3),
            Forms\Components\Select::make('category')
                ->label('Catégorie')
                ->options(PlantActionType::CATEGORIES)
                ->required(),
            Forms\Components\TextInput::make('icon')
                ->label('Icône')
                ->maxLength(50)
                ->helperText('Classe Font Awesome (ex: fa-cut, fa-tint)'),
            Forms\Components\TextInput::make('color')
                ->label('Couleur badge')
                ->maxLength(30)
                ->helperText('Classe Bootstrap (ex: bg-success, bg-danger)'),
            Forms\Components\Select::make('applies_to')
                ->label("S'applique à")
                ->options(PlantActionType::APPLIES_TO)
                ->default('all'),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
            Forms\Components\TextInput::make('sort_order')
                ->label('Ordre')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn (string $state) => PlantActionType::CATEGORIES[$state] ?? $state)
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('icon')->label('Icône'),
                Tables\Columns\TextColumn::make('applies_to')
                    ->label("S'applique à")
                    ->formatStateUsing(fn (string $state) => PlantActionType::APPLIES_TO[$state] ?? $state),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
                Tables\Columns\TextColumn::make('actions_count')->label('Actions')->counts('actions')->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Ordre')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(PlantActionType::CATEGORIES),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
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
            'index' => Pages\ListPlantActionTypes::route('/'),
            'create' => Pages\CreatePlantActionType::route('/create'),
            'edit' => Pages\EditPlantActionType::route('/{record}/edit'),
        ];
    }
}
