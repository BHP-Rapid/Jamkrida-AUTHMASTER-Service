<?php

namespace App\Repositories;

use App\Models\MasterMenuRoleMapping;

class MasterMenuRoleMappingRepository
{
    public function findByRoleAndMenu(int|string $roleId, int|string $menuIdentifier): ?MasterMenuRoleMapping
    {
        return MasterMenuRoleMapping::query()
            ->where('role_id', $roleId)
            ->when(
                is_numeric($menuIdentifier),
                fn ($query) => $query->where('menu_id', $menuIdentifier),
                fn ($query) => $query->whereHas('menu', fn ($menuQuery) => $menuQuery->where('menu_code', $menuIdentifier))
            )
            ->first();
    }

    public function hasPermission(int|string $roleId, int|string $menuIdentifier, string $action): bool
    {
        $mapping = $this->findByRoleAndMenu($roleId, $menuIdentifier);

        if (! $mapping) {
            return false;
        }

        return match ($action) {
            'view' => (bool) $mapping->can_view,
            'create' => (bool) $mapping->can_create,
            'edit' => (bool) $mapping->can_edit,
            'delete' => (bool) $mapping->can_delete,
            'approve' => (bool) $mapping->can_approve,
            default => false,
        };
    }
}
