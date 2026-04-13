<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $table = 'tenant';

    protected $primaryKey = 'id';

    protected $fillable = [
        'tenant_id',
        'institution_id',
        'parent_id',
        'name',
        'logo',
        'primary_color',
        'config',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
