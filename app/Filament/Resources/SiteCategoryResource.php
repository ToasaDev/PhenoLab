<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteCategoryResource\Pages;
use App\Models\SiteCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteCategoryResource extends Resource
{
    protected static ?string $model = SiteCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Terrain';

    protected static ?string $navigationLabel = 'Categories de sites';

    protected static ?string $modelLabel = 'Categorie de site';

    protected static ?string $pluralModelLabel = 'Categories de sites';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identification')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(150),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->maxLength(180)
                        ->helperText('Laisser vide pour generation automatique a partir du nom.')
                        ->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Hierarchie')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('parent_id')
                        ->label('Categorie parente (optionnel)')
                        ->relationship(
                            'parent',
                            'name',
                            modifyQueryUsing: fn ($query, ?SiteCategory $record) => $record
                                ? $query->whereNotIn('id', $record->descendantIds())
                                : $query
                        )
                        ->getOptionLabelFromRecordUsing(fn (SiteCategory $record) => $record->breadcrumb())
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Permet une organisation arborescente (ex: Rapallo > Parc Casale).'),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Ordre d\'affichage')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ]),

            Forms\Components\Section::make('Apparence')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('icon')
                        ->label('Icone (FontAwesome)')
                        ->maxLength(50)
                        ->placeholder('fa-tree'),
                    Forms\Components\TextInput::make('color')
                        ->label('Couleur')
                        ->maxLength(30)
                        ->placeholder('primary | #3b82f6'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (SiteCategory $record) => $record->breadcrumb()),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sites_count')
                    ->label('Sites')
                    ->counts('sites')
                    ->badge(),
                Tables\Columns\TextColumn::make('children_count')
                    ->label('Sous-cat.')
                    ->counts('children')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->relationship('parent', 'name')
                    ->getOptionLabelFromRecordUsing(fn (SiteCategory $record) => $record->breadcrumb())
                    ->searchable()
                    ->preload(),
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
            'index'  => Pages\ListSiteCategories::route('/'),
            'create' => Pages\CreateSiteCategory::route('/create'),
            'edit'   => Pages\EditSiteCategory::route('/{record}/edit'),
        ];
    }
}
