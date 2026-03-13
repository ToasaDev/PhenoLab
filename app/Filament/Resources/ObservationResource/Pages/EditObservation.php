<?php

namespace App\Filament\Resources\ObservationResource\Pages;

use App\Filament\Resources\ObservationResource;
use Filament\Resources\Pages\EditRecord;

class EditObservation extends EditRecord
{
    protected static string $resource = ObservationResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
