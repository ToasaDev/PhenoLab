<?php

namespace App\Filament\Resources\ObservationPhotoResource\Pages;

use App\Filament\Resources\ObservationPhotoResource;
use Filament\Resources\Pages\EditRecord;

class EditObservationPhoto extends EditRecord
{
    protected static string $resource = ObservationPhotoResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
