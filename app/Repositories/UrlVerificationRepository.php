<?php

namespace App\Repositories;

use App\Models\UrlVerification;

class UrlVerificationRepository
{
    public function findByUrlKey(string $urlKey): ?UrlVerification
    {
        return UrlVerification::query()
            ->where('url_key', $urlKey)
            ->first();
    }

    public function create(array $payload): UrlVerification
    {
        return UrlVerification::query()->create($payload);
    }
}
