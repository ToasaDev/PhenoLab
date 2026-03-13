<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $icons = [
            'created' => 'fa-plus-circle', 'updated' => 'fa-edit', 'deleted' => 'fa-trash',
            'replaced' => 'fa-exchange-alt', 'marked_dead' => 'fa-skull-crossbones',
            'validated' => 'fa-check-circle', 'uploaded' => 'fa-upload',
            'imported' => 'fa-file-import', 'synced' => 'fa-sync',
        ];
        $colors = [
            'created' => 'success', 'updated' => 'info', 'deleted' => 'danger',
            'replaced' => 'warning', 'marked_dead' => 'dark', 'validated' => 'primary',
            'uploaded' => 'secondary', 'imported' => 'info', 'synced' => 'info',
        ];

        return [
            'id' => $this->id,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor ? [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'username' => $this->actor->name,
            ] : null),
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'entity_label' => $this->entity_label,
            'is_public' => $this->is_public,
            'metadata' => $this->metadata,
            'icon' => $icons[$this->action] ?? 'fa-circle',
            'color' => $colors[$this->action] ?? 'secondary',
            'is_system' => $this->actor_id === null,
            'timestamp' => $this->created_at?->diffForHumans(),
            'created_at' => $this->created_at,
        ];
    }
}
