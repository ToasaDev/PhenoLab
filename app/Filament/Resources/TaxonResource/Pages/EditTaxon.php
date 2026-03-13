<?php

namespace App\Filament\Resources\TaxonResource\Pages;

use App\Filament\Resources\TaxonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxon extends EditRecord
{
    protected static string $resource = TaxonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
