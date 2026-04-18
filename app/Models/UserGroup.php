<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UserGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_memberships', 'group_id', 'user_id')
                    ->withPivot('role', 'created_at');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UserGroupMembership::class, 'group_id');
    }

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class, 'group_id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'group_id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(UserPlantTag::class, 'group_id');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ─────────────────────────────────────────────

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('users.id', $userId)->exists();
    }

    public function isOwner(int $userId): bool
    {
        return $this->owner_id === $userId;
    }

    // ── Boot ───────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }
}
