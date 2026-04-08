<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginAttemptResource\Pages;
use App\Models\LoginAttempt;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoginAttemptResource extends Resource
{
    protected static ?string $model = LoginAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Systeme';

    protected static ?string $navigationLabel = 'Tentatives de connexion';

    protected static ?string $modelLabel = 'Tentative';

    protected static ?string $pluralModelLabel = 'Tentatives de connexion';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->is_superuser === true;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = LoginAttempt::where('is_honeypot', true)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Raison')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'honeypot'   => 'danger',
                        'blocked_ip' => 'danger',
                        'invalid'    => 'warning',
                        'success'    => 'success',
                        default      => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_honeypot')
                    ->label('🍯')
                    ->boolean(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username tenté')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User-Agent')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->user_agent),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reason')
                    ->label('Raison')
                    ->options([
                        'honeypot'   => 'Honeypot',
                        'blocked_ip' => 'IP bloquée',
                        'invalid'    => 'Identifiants invalides',
                        'success'    => 'Connexion réussie',
                    ]),
                Tables\Filters\Filter::make('honeypot_only')
                    ->label('Honeypot uniquement')
                    ->query(fn ($query) => $query->where('is_honeypot', true)),
                Tables\Filters\Filter::make('last_24h')
                    ->label('Dernières 24h')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subDay())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginAttempts::route('/'),
        ];
    }
}
