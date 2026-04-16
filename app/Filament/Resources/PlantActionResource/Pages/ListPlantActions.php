<?php

namespace App\Filament\Resources\PlantActionResource\Pages;

use App\Filament\Resources\PlantActionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlantActions extends ListRecords
{
    protected static string $resource = PlantActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
