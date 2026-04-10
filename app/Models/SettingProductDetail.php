<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingProductDetail extends Model
{
    use HasFactory;

    protected $table = 'setting_product_dtl';

    protected $primaryKey = 'id';

    protected $fillable = [
        'hdr_id',
        'product_id',
        'lampiran',
        'reason_claim',
        'key',
        'value',
        'is_mandatory',
    ];

    public function settingHeader(): BelongsTo
    {
        return $this->belongsTo(Settings::class, 'hdr_id', 'id');
    }
}
