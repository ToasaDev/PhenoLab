<?php

namespace App\Filament\Resources\PlantActionTypeResource\Pages;

use App\Filament\Resources\PlantActionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlantActionTypes extends ListRecords
{
    protected static string $resource = PlantActionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
