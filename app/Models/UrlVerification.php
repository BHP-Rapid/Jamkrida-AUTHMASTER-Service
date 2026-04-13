<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrlVerification extends Model
{
    use HasFactory;

    protected $table = 'url_verification';

    protected $fillable = [
        'user_id',
        'url_key',
        'valid_before',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'valid_before' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
