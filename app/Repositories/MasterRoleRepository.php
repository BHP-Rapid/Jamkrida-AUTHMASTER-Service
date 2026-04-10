<?php

namespace App\Repositories;

use App\Models\MasterRole;

class MasterRoleRepository
{
    public function findById(int|string $id): ?MasterRole
    {
        return MasterRole::query()->find($id);
    }

    public function findByIdentifier(string $identifier): ?MasterRole
    {
        return MasterRole::query()
            ->where('role_code', $identifier)
            ->orWhere('role_name', $identifier)
            ->first();
    }
}
