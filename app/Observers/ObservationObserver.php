<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Observation;
use Illuminate\Support\Facades\Auth;

class ObservationObserver
{
    public function created(Observation $observation): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_CREATED,
            ActivityLog::ENTITY_OBSERVATION,
            $observation->id,
            "Observation du {$observation->observation_date->format('d/m/Y')}",
            Auth::id(),
            ['plant_id' => $observation->plant_id, 'stage_id' => $observation->phenological_stage_id],
        );
    }

    public function updated(Observation $observation): void
    {
        $action = ActivityLog::ACTION_UPDATED;

        if ($observation->wasChanged('is_validated') && $observation->is_validated) {
            $action = ActivityLog::ACTION_VALIDATED;
        }

        ActivityLog::log(
            $action,
            ActivityLog::ENTITY_OBSERVATION,
            $observation->id,
            "Observation du {$observation->observation_date->format('d/m/Y')}",
            Auth::id(),
            ['changed' => $observation->getChanges()],
        );
    }

    public function deleted(Observation $observation): void
    {
        ActivityLog::log(
            ActivityLog::ACTION_DELETED,
            ActivityLog::ENTITY_OBSERVATION,
            $observation->id,
            "Observation du {$observation->observation_date->format('d/m/Y')}",
            Auth::id(),
        );
    }
}
