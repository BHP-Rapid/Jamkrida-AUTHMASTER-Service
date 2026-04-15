<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $table = 'refresh_tokens';

    protected $fillable = [
        'token_hash',
        'auth_type',
        'subject_id',
        'user_id',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'replaced_by_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'replaced_by_id' => 'integer',
    ];
}
