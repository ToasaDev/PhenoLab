<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_staff || $this->is_superuser;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_staff' => 'boolean',
            'is_superuser' => 'boolean',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────

    public function ownedSites(): HasMany
    {
        return $this->hasMany(Site::class, 'owner_id');
    }

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class, 'owner_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'observer_id');
    }

    public function validatedObservations(): HasMany
    {
        return $this->hasMany(Observation::class, 'validated_by_id');
    }

    public function plantPhotos(): HasMany
    {
        return $this->hasMany(PlantPhoto::class, 'photographer_id');
    }

    public function observationPhotos(): HasMany
    {
        return $this->hasMany(ObservationPhoto::class, 'photographer_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }

    public function plantPositions(): HasMany
    {
        return $this->hasMany(PlantPosition::class, 'owner_id');
    }
}
