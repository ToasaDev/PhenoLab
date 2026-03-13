<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Taxonomie';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $modelLabel = 'Categorie';

    protected static ?string $pluralModelLabel = 'Categories';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3),
            Forms\Components\TextInput::make('icon')
                ->label('Icone')
                ->maxLength(50)
                ->helperText('Nom de classe Font Awesome (ex: fa-tree)'),
            Forms\Components\Select::make('category_type')
                ->label('Type')
                ->options([
                    'tree' => 'Arbre',
                    'shrub' => 'Arbuste',
                    'plant' => 'Plante',
                    'animal' => 'Animal',
                    'insect' => 'Insecte',
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category_type')->label('Type')->sortable()->badge(),
                Tables\Columns\TextColumn::make('icon')->label('Icone'),
                Tables\Columns\TextColumn::make('plants_count')->label('Plantes')->counts('plants')->sortable(),
            ])
            ->filters([])
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
