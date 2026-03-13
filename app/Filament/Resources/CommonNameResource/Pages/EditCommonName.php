<?php

namespace App\Filament\Resources\CommonNameResource\Pages;

use App\Filament\Resources\CommonNameResource;
use Filament\Resources\Pages\EditRecord;

class EditCommonName extends EditRecord
{
    protected static string $resource = CommonNameResource::class;
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
