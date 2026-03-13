<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhenologicalStageResource\Pages;
use App\Models\PhenologicalStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PhenologicalStageResource extends Resource
{
    protected static ?string $model = PhenologicalStage::class;

    protected static ?string $navigationIcon = 'heroicon-o-sun';

    protected static ?string $navigationGroup = 'Taxonomie';

    protected static ?string $navigationLabel = 'Stades phenologiques';

    protected static ?string $modelLabel = 'Stade phenologique';

    protected static ?string $pluralModelLabel = 'Stades phenologiques';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('stage_code')
                ->label('Code stade')
                ->required()
                ->maxLength(20),
            Forms\Components\TextInput::make('stage_description')
                ->label('Description du stade')
                ->required()
                ->maxLength(500),
            Forms\Components\TextInput::make('main_event_code')
                ->label('Code evenement principal')
                ->maxLength(20),
            Forms\Components\TextInput::make('main_event_description')
                ->label('Description evenement')
                ->maxLength(500),
            Forms\Components\TextInput::make('phenological_scale')
                ->label('Echelle phenologique')
                ->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stage_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stage_description')->label('Description')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('main_event_code')->label('Evenement')->sortable(),
                Tables\Columns\TextColumn::make('main_event_description')->label('Desc. evenement')->limit(40),
                Tables\Columns\TextColumn::make('phenological_scale')->label('Echelle'),
                Tables\Columns\TextColumn::make('observations_count')->label('Observations')->counts('observations')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('main_event_code')
                    ->label('Evenement')
                    ->options(fn () => PhenologicalStage::whereNotNull('main_event_code')->distinct()->pluck('main_event_description', 'main_event_code')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('stage_code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhenologicalStages::route('/'),
            'create' => Pages\CreatePhenologicalStage::route('/create'),
            'edit' => Pages\EditPhenologicalStage::route('/{record}/edit'),
        ];
    }
}
