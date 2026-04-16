<?php

namespace App\Filament\Resources\SiteCategoryResource\Pages;

use App\Filament\Resources\SiteCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditSiteCategory extends EditRecord
{
    protected static string $resource = SiteCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
