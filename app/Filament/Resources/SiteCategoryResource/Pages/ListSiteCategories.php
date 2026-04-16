<?php

namespace App\Filament\Resources\SiteCategoryResource\Pages;

use App\Filament\Resources\SiteCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListSiteCategories extends ListRecords
{
    protected static string $resource = SiteCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
