<?php

namespace App\Filament\Resources\UserGroupResource\Pages;

use App\Filament\Resources\UserGroupResource;
use App\Models\UserGroupMembership;
use Filament\Resources\Pages\CreateRecord;

class CreateUserGroup extends CreateRecord
{
    protected static string $resource = UserGroupResource::class;

    protected function afterCreate(): void
    {
        // Auto-add owner as member with 'owner' role
        UserGroupMembership::updateOrCreate(
            ['user_id' => $this->record->owner_id, 'group_id' => $this->record->id],
            ['role' => UserGroupMembership::ROLE_OWNER],
        );
    }
}
