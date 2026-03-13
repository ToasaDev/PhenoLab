<?php

namespace App\Filament\Resources\PhenologicalStageResource\Pages;

use App\Filament\Resources\PhenologicalStageResource;
use Filament\Resources\Pages\ListRecords;

class ListPhenologicalStages extends ListRecords
{
    protected static string $resource = PhenologicalStageResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
