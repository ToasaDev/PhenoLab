<?php

namespace App\Filament\Resources\ObservationPhotoResource\Pages;

use App\Filament\Resources\ObservationPhotoResource;
use Filament\Resources\Pages\ListRecords;

class ListObservationPhotos extends ListRecords
{
    protected static string $resource = ObservationPhotoResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
