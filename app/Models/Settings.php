<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settings extends Model
{
    use HasFactory;

    protected $table = 'setting_hdr';

    protected $primaryKey = 'id';

    protected $fillable = [
        'mitra_id',
        'module',
    ];

    public function productDetails(): HasMany
    {
        return $this->hasMany(SettingProductDetail::class, 'hdr_id', 'id');
    }
}
