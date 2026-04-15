<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifMitra extends Model
{
    protected $table = 'notif_mitra';

    protected $fillable = [
        'mitra_user_id',
        'title',
        'message',
        'is_read',
        'url',
        'created_at',
        'updated_at',
    ];
}
