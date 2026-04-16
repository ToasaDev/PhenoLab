<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlantActionResource\Pages;
use App\Models\PlantAction;
use App\Models\PlantActionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlantActionResource extends Resource
{
    protected static ?string $model = PlantAction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Interventions';

    protected static ?string $navigationLabel = 'Actions sur plantes';

    protected static ?string $modelLabel = 'Action';

    protected static ?string $pluralModelLabel = 'Actions sur plantes';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Action')
                ->schema([
                    Forms\Components\Select::make('plant_id')
                        ->label('Plante')
                        ->relationship('plant', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('action_type_id')
                        ->label("Type d'action")
                        ->relationship('actionType', 'name', fn ($query) => $query->active()->orderBy('sort_order'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('action_date')
                        ->label('Date')
                        ->required()
                        ->default(now()),
                    Forms\Components\TextInput::make('title')
                        ->label('Titre (optionnel)')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3),
                ]),
            Forms\Components\Section::make('Détails')
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('product_name')
                        ->label('Produit utilisé')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('quantity')
                        ->label('Quantité')
                        ->numeric(),
                    Forms\Components\TextInput::make('unit')
                        ->label('Unité')
                        ->maxLength(30)
                        ->helperText('Ex: litres, kg, ml'),
                    Forms\Components\TextInput::make('dosage')
                        ->label('Dosage')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('method')
                        ->label('Méthode')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('cost')
                        ->label('Coût')
                        ->numeric()
                        ->prefix('€'),
                    Forms\Components\TextInput::make('weather_conditions')
                        ->label('Conditions météo')
                        ->maxLength(100),
                    Forms\Components\Select::make('performed_by')
                        ->label('Réalisé par (utilisateur)')
                        ->relationship('performer', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('performer_name')
                        ->label('Réalisé par (texte libre)')
                        ->maxLength(100)
                        ->helperText('Si la personne n\'a pas de compte'),
                    Forms\Components\Toggle::make('is_private')
                        ->label('Privé'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('action_date')->label('Date')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('plant.name')->label('Plante')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('actionType.name')->label('Type')->sortable()->badge(),
                Tables\Columns\TextColumn::make('actionType.category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn (string $state) => PlantActionType::CATEGORIES[$state] ?? $state),
                Tables\Columns\TextColumn::make('title')->label('Titre')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('product_name')->label('Produit')->limit(20)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('performer_display')
                    ->label('Par')
                    ->getStateUsing(fn (PlantAction $record) => $record->performer_display),
                Tables\Columns\TextColumn::make('cost')->label('Coût')->money('EUR')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('action_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action_type_id')
                    ->label("Type d'action")
                    ->relationship('actionType', 'name'),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(PlantActionType::CATEGORIES)
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereHas('actionType', fn ($q) => $q->where('category', $data['value']))
                        : $query
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlantActions::route('/'),
            'create' => Pages\CreatePlantAction::route('/create'),
            'edit' => Pages\EditPlantAction::route('/{record}/edit'),
        ];
    }
}
