<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class UserPlantTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'creator_id',
        'group_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public const COLORS = [
        'primary'   => 'Bleu',
        'success'   => 'Vert',
        'warning'   => 'Jaune',
        'danger'    => 'Rouge',
        'info'      => 'Cyan',
        'secondary' => 'Gris',
        'dark'      => 'Sombre',
    ];

    // ── Relationships ──────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }

    public function plants(): BelongsToMany
    {
        return $this->belongsToMany(Plant::class, 'plant_tag_assignments', 'tag_id', 'plant_id')
                    ->withPivot('assigned_by_id', 'created_at');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Tags visible to a given user: own tags + tags from shared groups.
     */
    public function scopeVisibleTo($query, User $user)
    {
        $groupIds = $user->groupIds();

        return $query->where(function ($q) use ($user, $groupIds) {
            $q->where('creator_id', $user->id);
            if (!empty($groupIds)) {
                $q->orWhereIn('group_id', $groupIds);
            }
        });
    }

    // ── Boot ───────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }
}
