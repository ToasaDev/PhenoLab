<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'action',
        'entity_type',
        'entity_id',
        'entity_label',
        'is_public',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'metadata'  => 'array',
        ];
    }

    // ── Action constants ────────────────────────────────────────────

    public const ACTION_CREATED     = 'created';
    public const ACTION_UPDATED     = 'updated';
    public const ACTION_DELETED     = 'deleted';
    public const ACTION_REPLACED    = 'replaced';
    public const ACTION_MARKED_DEAD = 'marked_dead';
    public const ACTION_VALIDATED   = 'validated';
    public const ACTION_UPLOADED    = 'uploaded';
    public const ACTION_IMPORTED    = 'imported';
    public const ACTION_SYNCED      = 'synced';

    public const ACTIONS = [
        self::ACTION_CREATED     => 'Cree',
        self::ACTION_UPDATED     => 'Mis a jour',
        self::ACTION_DELETED     => 'Supprime',
        self::ACTION_REPLACED    => 'Remplace',
        self::ACTION_MARKED_DEAD => 'Marque comme mort',
        self::ACTION_VALIDATED   => 'Valide',
        self::ACTION_UPLOADED    => 'Telecharge',
        self::ACTION_IMPORTED    => 'Importe',
        self::ACTION_SYNCED      => 'Synchronise',
    ];

    // ── Entity type constants ───────────────────────────────────────

    public const ENTITY_OBSERVATION = 'observation';
    public const ENTITY_PLANT       = 'plant';
    public const ENTITY_TAXON       = 'taxon';
    public const ENTITY_PHOTO       = 'photo';
    public const ENTITY_SITE        = 'site';
    public const ENTITY_POSITION    = 'position';
    public const ENTITY_SYSTEM      = 'system';

    public const ENTITY_TYPES = [
        self::ENTITY_OBSERVATION => 'Observation',
        self::ENTITY_PLANT       => 'Plante',
        self::ENTITY_TAXON       => 'Taxon',
        self::ENTITY_PHOTO       => 'Photo',
        self::ENTITY_SITE        => 'Site',
        self::ENTITY_POSITION    => 'Position',
        self::ENTITY_SYSTEM      => 'Systeme',
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────

    /**
     * Filter to public activities only.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Filter activities for a specific entity.
     */
    public function scopeForEntity(Builder $query, string $entityType, ?int $entityId = null): Builder
    {
        $query->where('entity_type', $entityType);

        if ($entityId !== null) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }

    // ── Static helper ───────────────────────────────────────────────

    /**
     * Create an activity log entry.
     */
    public static function log(
        string $action,
        string $entityType,
        int $entityId,
        string $entityLabel,
        ?int $actorId = null,
        ?array $metadata = null,
        bool $isPublic = true,
    ): static {
        return static::create([
            'actor_id'     => $actorId,
            'action'       => $action,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'entity_label' => $entityLabel,
            'is_public'    => $isPublic,
            'metadata'     => $metadata ?? [],
        ]);
    }
}
