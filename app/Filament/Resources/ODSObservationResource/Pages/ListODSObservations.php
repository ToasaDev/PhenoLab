<?php

namespace App\Filament\Resources\ODSObservationResource\Pages;

use App\Filament\Resources\ODSObservationResource;
use Filament\Resources\Pages\ListRecords;

class ListODSObservations extends ListRecords
{
    protected static string $resource = ODSObservationResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
