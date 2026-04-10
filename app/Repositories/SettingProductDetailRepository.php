<?php

namespace App\Repositories;

use App\Models\SettingProductDetail;

class SettingProductDetailRepository
{
    public function findByKey(string $key): ?SettingProductDetail
    {
        return SettingProductDetail::query()
            ->where('key', $key)
            ->first();
    }

    public function getIntValue(string $key, int $default = 0): int
    {
        return (int) ($this->findByKey($key)?->value ?? $default);
    }
}
