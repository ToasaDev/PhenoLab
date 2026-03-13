<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelaObservationResource\Pages;
use App\Models\TelaObservation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelaObservationResource extends Resource
{
    protected static ?string $model = TelaObservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Donnees externes';

    protected static ?string $navigationLabel = 'Observations Tela';

    protected static ?string $modelLabel = 'Observation Tela';

    protected static ?string $pluralModelLabel = 'Observations Tela';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('binomial_name')->label('Nom binomial')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('site_name')->label('Station')->searchable(),
                Tables\Columns\TextColumn::make('stage_description')->label('Stade')->limit(30),
                Tables\Columns\TextColumn::make('stage_code')->label('Code stade'),
                Tables\Columns\TextColumn::make('environment')->label('Environnement'),
                Tables\Columns\TextColumn::make('year')->label('Annee')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('genus')
                    ->label('Genre')
                    ->options(fn () => TelaObservation::whereNotNull('genus')->distinct()->orderBy('genus')->limit(100)->pluck('genus', 'genus')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelaObservations::route('/'),
        ];
    }
}
