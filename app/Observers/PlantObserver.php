<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Plant;
use Illuminate\Support\Facades\Auth;

class PlantObserver
{
    public function created(Plant $plant): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_CREATED,
            ActivityLog::ENTITY_PLANT,
            $plant->id,
            $plant->name,
            Auth::id(),
            ['site_id' => $plant->site_id, 'taxon_id' => $plant->taxon_id],
        );
    }

    public function updated(Plant $plant): void
    {
        $action = ActivityLog::ACTION_UPDATED;

        if ($plant->wasChanged('status')) {
            if ($plant->status === 'dead') {
                $action = ActivityLog::ACTION_MARKED_DEAD;
            } elseif ($plant->status === 'replaced') {
                $action = ActivityLog::ACTION_REPLACED;
            }
        }

        ActivityLog::log(
            $action,
            ActivityLog::ENTITY_PLANT,
            $plant->id,
            $plant->name,
            Auth::id(),
            ['changed' => $plant->getChanges()],
        );
    }

    public function deleted(Plant $plant): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_DELETED,
            ActivityLog::ENTITY_PLANT,
            $plant->id,
            $plant->name,
            Auth::id(),
        );
    }
}
