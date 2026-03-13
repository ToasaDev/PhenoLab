<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $roleFields = [
            'is_staff' => $data['is_staff'] ?? false,
            'is_superuser' => $data['is_superuser'] ?? false,
        ];
        unset($data['is_staff'], $data['is_superuser']);

        $record->update($data);
        $record->forceFill($roleFields)->save();

        return $record;
    }
}
