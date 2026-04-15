<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTransactions extends Model
{
    protected $table = 'audit_trail_transactions';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'http_method',
        'api_endpoint',
        'target_table',
        'is_success',
        'user_email',
        'user_role',
        'request_payload',
        'created_by_id',
        'created_by_name',
        'updated_by_id',
        'updated_by_name',
        'created_at',
        'updated_at',
    ];
}
