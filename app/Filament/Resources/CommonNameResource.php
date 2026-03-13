<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommonNameResource\Pages;
use App\Models\CommonName;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommonNameResource extends Resource
{
    protected static ?string $model = CommonName::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationGroup = 'Taxonomie';

    protected static ?string $navigationLabel = 'Noms communs';

    protected static ?string $modelLabel = 'Nom commun';

    protected static ?string $pluralModelLabel = 'Noms communs';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('taxon_id')->label('Taxon')
                ->relationship('taxon', 'binomial_name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('name')->label('Nom')->required()->maxLength(255),
            Forms\Components\Select::make('language')->label('Langue')
                ->options(['fr' => 'Francais', 'en' => 'Anglais', 'it' => 'Italien', 'de' => 'Allemand'])
                ->required(),
            Forms\Components\TextInput::make('region')->label('Region')->maxLength(100),
            Forms\Components\Toggle::make('is_primary')->label('Nom principal'),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('taxon.binomial_name')->label('Taxon')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('language')->label('Langue')->badge()->sortable(),
                Tables\Columns\TextColumn::make('region')->label('Region'),
                Tables\Columns\IconColumn::make('is_primary')->label('Principal')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('language')->label('Langue')
                    ->options(['fr' => 'Francais', 'en' => 'Anglais', 'it' => 'Italien']),
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
            'index' => Pages\ListCommonNames::route('/'),
            'create' => Pages\CreateCommonName::route('/create'),
            'edit' => Pages\EditCommonName::route('/{record}/edit'),
        ];
    }
}
