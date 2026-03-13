<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ODSObservationResource\Pages;
use App\Models\ODSObservation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ODSObservationResource extends Resource
{
    protected static ?string $model = ODSObservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud';

    protected static ?string $navigationGroup = 'Donnees externes';

    protected static ?string $navigationLabel = 'Observations ODS';

    protected static ?string $modelLabel = 'Observation ODS';

    protected static ?string $pluralModelLabel = 'Observations ODS';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('scientific_name')->label('Nom scientifique')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('vernacular_name')->label('Nom vernaculaire')->searchable(),
                Tables\Columns\TextColumn::make('station_name')->label('Station')->searchable(),
                Tables\Columns\TextColumn::make('phenological_stage')->label('Stade')->limit(30),
                Tables\Columns\TextColumn::make('department')->label('Departement')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->label('Departement')
                    ->options(fn () => ODSObservation::whereNotNull('department')->distinct()->orderBy('department')->pluck('department', 'department')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListODSObservations::route('/'),
        ];
    }
}
