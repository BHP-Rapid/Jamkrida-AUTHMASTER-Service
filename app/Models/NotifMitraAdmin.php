<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifMitraAdmin extends Model
{
    protected $table = 'notif_mitra_admin';

    protected $fillable = [
        'bulk_no',
        'title',
        'message',
        'recipient_type',
        'recipient',
        'created_at',
        'updated_at',
    ];
}
