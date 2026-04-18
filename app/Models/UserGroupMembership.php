<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGroupMembership extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'group_id',
        'role',
    ];

    public const ROLE_OWNER  = 'owner';
    public const ROLE_MEMBER = 'member';

    // ── Relationships ──────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }
}
