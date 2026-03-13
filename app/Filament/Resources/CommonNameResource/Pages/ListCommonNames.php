<?php

namespace App\Filament\Resources\CommonNameResource\Pages;

use App\Filament\Resources\CommonNameResource;
use Filament\Resources\Pages\ListRecords;

class ListCommonNames extends ListRecords
{
    protected static string $resource = CommonNameResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
