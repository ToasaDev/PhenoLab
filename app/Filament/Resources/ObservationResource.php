<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ObservationResource\Pages;
use App\Models\Observation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ObservationResource extends Resource
{
    protected static ?string $model = Observation::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationGroup = 'Observations';

    protected static ?string $navigationLabel = 'Observations';

    protected static ?string $modelLabel = 'Observation';

    protected static ?string $pluralModelLabel = 'Observations';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Observation')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('observation_date')->label('Date')->required(),
                    Forms\Components\Select::make('plant_id')->label('Plante')
                        ->relationship('plant', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('phenological_stage_id')->label('Stade phenologique')
                        ->relationship('phenologicalStage', 'stage_description')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('observer_id')->label('Observateur')
                        ->relationship('observer', 'name')
                        ->searchable(),
                    Forms\Components\TextInput::make('intensity')->label('Intensite')->numeric()->minValue(1)->maxValue(5),
                    Forms\Components\TextInput::make('confidence_level')->label('Niveau de confiance')->numeric()->minValue(1)->maxValue(5)->default(3),
                    Forms\Components\Textarea::make('notes')->label('Notes')->rows(3)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Conditions meteo')
                ->columns(3)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('temperature')->label('Temperature (C)')->numeric(),
                    Forms\Components\Select::make('weather_condition')
                        ->label('Meteo')
                        ->options([
                            'sunny' => 'Ensoleille',
                            'partly_cloudy' => 'Partiellement nuageux',
                            'cloudy' => 'Nuageux',
                            'overcast' => 'Couvert',
                            'rainy' => 'Pluvieux',
                            'stormy' => 'Orageux',
                            'snowy' => 'Neigeux',
                            'foggy' => 'Brumeux',
                        ]),
                    Forms\Components\TextInput::make('humidity')->label('Humidite (%)')->numeric()->minValue(0)->maxValue(100),
                    Forms\Components\TextInput::make('wind_speed')->label('Vent (km/h)')->numeric(),
                    Forms\Components\TimePicker::make('time_of_day')->label('Heure'),
                ]),

            Forms\Components\Section::make('Validation & Visibilite')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('is_validated')->label('Validee'),
                    Forms\Components\Toggle::make('is_public')->label('Publique')->default(true),
                    Forms\Components\Select::make('validated_by_id')->label('Validee par')
                        ->relationship('validatedBy', 'name')
                        ->searchable()
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('validation_date')->label('Date de validation')->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('observation_date')->label('Date')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('plant.name')->label('Plante')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phenologicalStage.stage_description')->label('Stade')->limit(30)->sortable(),
                Tables\Columns\TextColumn::make('observer.name')->label('Observateur')->sortable(),
                Tables\Columns\TextColumn::make('intensity')->label('Intensite')->sortable(),
                Tables\Columns\TextColumn::make('confidence_level')->label('Confiance')->sortable(),
                Tables\Columns\IconColumn::make('is_validated')->label('Validee')->boolean(),
                Tables\Columns\IconColumn::make('is_public')->label('Publique')->boolean(),
                Tables\Columns\TextColumn::make('photos_count')->label('Photos')->counts('photos')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plant_id')->label('Plante')->relationship('plant', 'name')->searchable()->preload(),
                Tables\Filters\TernaryFilter::make('is_validated')->label('Validee'),
                Tables\Filters\TernaryFilter::make('is_public')->label('Publique'),
                Tables\Filters\SelectFilter::make('observer_id')->label('Observateur')->relationship('observer', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Observation $record) => ! $record->is_validated)
                    ->action(function (Observation $record) {
                        $record->update([
                            'is_validated' => true,
                            'validated_by_id' => Auth::id(),
                            'validation_date' => now(),
                        ]);
                        Notification::make()->title('Observation validee')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkValidate')
                        ->label('Valider les observations')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = Observation::whereIn('id', $records->pluck('id'))
                                ->update([
                                    'is_validated' => true,
                                    'validated_by_id' => Auth::id(),
                                    'validation_date' => now(),
                                ]);
                            Notification::make()->title("{$count} observation(s) validee(s)")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('makePublic')
                        ->label('Rendre publiques')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = Observation::whereIn('id', $records->pluck('id'))
                                ->update(['is_public' => true]);
                            Notification::make()->title("{$count} observation(s) rendues publiques")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('makePrivate')
                        ->label('Rendre privees')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = Observation::whereIn('id', $records->pluck('id'))
                                ->update(['is_public' => false]);
                            Notification::make()->title("{$count} observation(s) rendues privees")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('observation_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListObservations::route('/'),
            'create' => Pages\CreateObservation::route('/create'),
            'edit' => Pages\EditObservation::route('/{record}/edit'),
        ];
    }
}
