<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip',
        'username',
        'password_hash',
        'user_agent',
        'reason',
        'is_honeypot',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_honeypot' => 'boolean',
            'created_at'  => 'datetime',
        ];
    }
}
