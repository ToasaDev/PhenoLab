<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $roleFields = [
            'is_staff' => $data['is_staff'] ?? false,
            'is_superuser' => $data['is_superuser'] ?? false,
        ];
        unset($data['is_staff'], $data['is_superuser']);

        $record = static::getModel()::create($data);
        $record->forceFill($roleFields)->save();

        return $record;
    }
}
