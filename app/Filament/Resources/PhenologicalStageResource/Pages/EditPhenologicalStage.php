<?php

namespace App\Filament\Resources\PhenologicalStageResource\Pages;

use App\Filament\Resources\PhenologicalStageResource;
use Filament\Resources\Pages\EditRecord;

class EditPhenologicalStage extends EditRecord
{
    protected static string $resource = PhenologicalStageResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
