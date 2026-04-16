<?php

namespace App\Filament\Resources\PlantActionResource\Pages;

use App\Filament\Resources\PlantActionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlantAction extends EditRecord
{
    protected static string $resource = PlantActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
