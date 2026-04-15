<?php

namespace App\Repositories;

use App\Models\TenantMitra;
use Illuminate\Support\Collection;

class TenantMitraRepository
{
    public function getActiveList(): Collection
    {
        return TenantMitra::query()
            ->from('tenant_mitra as t')
            ->select(
                't.mitra_id as mitra_id',
                't.name as name_mitra',
            )
            ->whereNull('t.deleted_at')
            ->leftJoin('institution as i', 't.institution_id', '=', 'i.institution_id')
            ->leftJoin('tenant as m', 't.tenant_id', '=', 'm.tenant_id')
            ->get();
    }
}
