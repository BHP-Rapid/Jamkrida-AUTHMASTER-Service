<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserMitra extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'user_mitra';

    protected $fillable = [
        'id',
        'user_id',
        'mitra_id',
        'name',
        'email',
        'password',
        'last_login',
        'phone',
        'role',
        'status',
        'statusApproval',
        'suspend_until',
        'login_attempts',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'deleted_by',
        'is_delete',
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
            'auth_type' => 'mitra',
            'user_id' => $this->user_id,
            'email' => $this->email,
            'role' => $this->role,
            'mitra_id' => $this->mitra_id,
        ];
    }
}
