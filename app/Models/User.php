<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'mitra_id',
        'role',
        'email',
        'password',
        'last_login',
        'phone',
        'name',
        'status',
        'status_approval',
        'suspend_until',
        'login_attempts',
        'deleted_at',
        'is_delete',
        'deleted_by',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'suspend_until' => 'datetime',
        'login_attempts' => 'integer',
        'deleted_at' => 'datetime',
        'is_delete' => 'boolean',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'auth_type' => 'admin',
            'user_id' => $this->user_id ?? $this->getKey(),
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
