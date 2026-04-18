<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserGroupResource\Pages;
use App\Models\UserGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserGroupResource extends Resource
{
    protected static ?string $model = UserGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Utilisateurs';

    protected static ?string $navigationLabel = 'Groupes';

    protected static ?string $modelLabel = 'Groupe';

    protected static ?string $pluralModelLabel = 'Groupes';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(150),
            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->maxLength(180)
                ->unique(ignoreRecord: true)
                ->helperText('Généré automatiquement si vide'),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3),
            Forms\Components\Select::make('owner_id')
                ->label('Propriétaire')
                ->relationship('owner', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_count')
                    ->label('Membres')
                    ->counts('members')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plants_count')
                    ->label('Plantes')
                    ->counts('plants')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sites_count')
                    ->label('Sites')
                    ->counts('sites')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, UserGroup $record) {
                        if ($record->plants()->exists() || $record->sites()->exists()) {
                            $action->cancel();
                            $action->failureNotificationTitle('Ce groupe contient des plantes ou des sites. Retirez-les avant de supprimer.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UserGroupResource\RelationManagers\MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserGroups::route('/'),
            'create' => Pages\CreateUserGroup::route('/create'),
            'edit' => Pages\EditUserGroup::route('/{record}/edit'),
        ];
    }
}
