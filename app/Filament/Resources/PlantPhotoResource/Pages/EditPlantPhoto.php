<?php

namespace App\Filament\Resources\PlantPhotoResource\Pages;

use App\Filament\Resources\PlantPhotoResource;
use Filament\Resources\Pages\EditRecord;

class EditPlantPhoto extends EditRecord
{
    protected static string $resource = PlantPhotoResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
