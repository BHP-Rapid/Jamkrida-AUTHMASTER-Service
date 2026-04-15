<?php

namespace App\Repositories;

use App\Models\Mitra;
use Illuminate\Support\Collection;

class MitraRepository
{
    public function getPublicBankValues(): Collection
    {
        return Mitra::query()
            ->select('id', 'mitra_id as bank_code', 'name_mitra as bank_name')
            ->get();
    }

    public function create(array $payload): Mitra
    {
        return Mitra::query()->create($payload);
    }
}
