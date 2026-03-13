<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;

class SiteObserver
{
    public function created(Site $site): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_CREATED,
            ActivityLog::ENTITY_SITE,
            $site->id,
            $site->name,
            Auth::id(),
        );
    }

    public function updated(Site $site): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_UPDATED,
            ActivityLog::ENTITY_SITE,
            $site->id,
            $site->name,
            Auth::id(),
            ['changed' => $site->getChanges()],
        );
    }

    public function deleted(Site $site): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_DELETED,
            ActivityLog::ENTITY_SITE,
            $site->id,
            $site->name,
            Auth::id(),
        );
    }
}
