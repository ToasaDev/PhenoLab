<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Systeme';

    protected static ?string $navigationLabel = 'Journal d\'activite';

    protected static ?string $modelLabel = 'Activite';

    protected static ?string $pluralModelLabel = 'Journal d\'activite';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('actor.name')->label('Acteur')->searchable(),
                Tables\Columns\TextColumn::make('action')->label('Action')->badge()->sortable(),
                Tables\Columns\TextColumn::make('entity_type')->label('Type entite')->sortable(),
                Tables\Columns\TextColumn::make('entity_label')->label('Entite')->searchable(),
                Tables\Columns\IconColumn::make('is_public')->label('Public')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')->label('Action')
                    ->options(fn () => ActivityLog::distinct()->orderBy('action')->pluck('action', 'action')),
                Tables\Filters\SelectFilter::make('entity_type')->label('Type')
                    ->options(fn () => ActivityLog::distinct()->orderBy('entity_type')->pluck('entity_type', 'entity_type')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
