<?php

namespace App\Filament\Resources\PlantActionTypeResource\Pages;

use App\Filament\Resources\PlantActionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlantActionType extends EditRecord
{
    protected static string $resource = PlantActionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
