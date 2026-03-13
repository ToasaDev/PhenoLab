<?php

namespace App\Filament\Resources\TelaObservationResource\Pages;

use App\Filament\Resources\TelaObservationResource;
use Filament\Resources\Pages\ListRecords;

class ListTelaObservations extends ListRecords
{
    protected static string $resource = TelaObservationResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
