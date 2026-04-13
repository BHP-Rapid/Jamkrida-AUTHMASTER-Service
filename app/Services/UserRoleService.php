<?php

namespace App\Services;

use App\Repositories\MasterMenuRepository;
use App\Repositories\MasterMenuRoleMappingRepository;
use App\Repositories\MasterRoleRepository;
use Illuminate\Support\Collection;

class UserRoleService
{
    public function __construct(
        protected MasterRoleRepository $masterRoleRepository,
        protected MasterMenuRepository $masterMenuRepository,
        protected MasterMenuRoleMappingRepository $masterMenuRoleMappingRepository,
    ) {
    }

    public function getCurrentRoleAccess(object $user): array
    {
        $role = $this->resolveRoleFromUser($user);

        if (! $role) {
            return [
                'success' => false,
                'message' => 'User data not found',
                'status' => 404,
            ];
        }

        $listMenu = $this->buildAssignedMenuTree($role->id, (string) $role->type);

        return [
            'success' => true,
            'message' => 'Role access berhasil diambil.',
            'status' => 200,
            'data' => [
                'name' => $role->role_name,
                'path' => '/',
                'badge' => null,
                'sequence' => 1,
                'children' => $listMenu,
            ],
        ];
    }

    public function getAllRoles(array $payload): array
    {
        $filters = [];

        foreach (($payload['filter'] ?? []) as $filterItem) {
            match ($filterItem['id'] ?? null) {
                'role_code' => $filters['role_code'] = $filterItem['value'] ?? null,
                'role_name' => $filters['role_name'] = $filterItem['value'] ?? null,
                'type' => $filters['type'] = $filterItem['value'] ?? null,
                default => null,
            };
        }

        $sortColumns = [
            'role_code' => 'role_code',
            'role_name' => 'role_name',
            'type' => 'type',
        ];

        $sortColumn = $sortColumns[$payload['sort_column'] ?? ''] ?? 'role_code';
        $sortOrder = $payload['sort'] ?? 'desc';
        $perPage = (int) ($payload['show_page'] ?? 10);

        return [
            'success' => true,
            'message' => 'Data role berhasil diambil.',
            'status' => 200,
            'data' => $this->masterRoleRepository->paginate($filters, $sortColumn, $sortOrder, $perPage),
        ];
    }

    public function getAccessByRole(int $roleId): array
    {
        $role = $this->masterRoleRepository->findById($roleId);

        if (! $role) {
            return [
                'success' => false,
                'message' => 'Role not found.',
                'status' => 404,
            ];
        }

        return [
            'success' => true,
            'message' => 'Access role berhasil diambil.',
            'status' => 200,
            'data' => [
                'role_code' => $role->role_code,
                'role_name' => $role->role_name,
                'role_type' => $role->type,
                'listAccess' => $this->buildEditableMenuTree((int) $role->id, (string) $role->type),
            ],
        ];
    }

    public function updateRole(array $payload): array
    {
        $role = $this->masterRoleRepository->findById($payload['role_id']);

        if (! $role || empty($role->type)) {
            return [
                'success' => false,
                'message' => 'Invalid role type',
                'status' => 400,
            ];
        }

        $roleManagementMenu = $this->masterMenuRepository->findRoleManagementMenu((string) $role->type);
        $menuIdList = collect($payload['payload'])->pluck('menu_id')->all();

        if ($roleManagementMenu && in_array($roleManagementMenu->id, $menuIdList, true)) {
            return [
                'success' => false,
                'message' => 'Invalid menu payload (updating Role Management menu is not allowed)',
                'status' => 400,
            ];
        }

        $menus = $this->masterMenuRepository->findByIdsAndWebType($menuIdList, (string) $role->type);

        if ($menus->count() !== count(array_unique($menuIdList))) {
            return [
                'success' => false,
                'message' => 'Invalid menu payload (some menu items do not exist for this role type).',
                'status' => 400,
            ];
        }

        $menuMap = $menus->keyBy('id');

        foreach ($payload['payload'] as $payloadItem) {
            $menu = $menuMap->get($payloadItem['menu_id']);
            $requestedActions = array_values(array_unique($payloadItem['action'] ?? []));
            $availableActions = array_values($menu?->available_actions ?? []);

            if (array_diff($requestedActions, $availableActions)) {
                return [
                    'success' => false,
                    'message' => 'Invalid menu action',
                    'status' => 400,
                ];
            }
        }

        foreach ($payload['payload'] as $payloadItem) {
            $this->masterMenuRoleMappingRepository->updatePermissions(
                $payload['role_id'],
                $payloadItem['menu_id'],
                $payloadItem['action'] ?? [],
            );
        }

        return [
            'success' => true,
            'message' => 'Role updated successfully.',
            'status' => 200,
        ];
    }

    public function getRoleByType(string $roleType): array
    {
        return [
            'success' => true,
            'message' => 'Data role by type berhasil diambil.',
            'status' => 200,
            'data' => $this->masterRoleRepository->findByType($roleType),
        ];
    }

    protected function resolveRoleFromUser(object $user): ?object
    {
        if (isset($user->role_id) && $user->role_id) {
            return $this->masterRoleRepository->findById($user->role_id);
        }

        if (! isset($user->role) || ! $user->role) {
            return null;
        }

        if (is_numeric($user->role)) {
            return $this->masterRoleRepository->findById($user->role);
        }

        return $this->masterRoleRepository->findByIdentifier((string) $user->role);
    }

    protected function buildAssignedMenuTree(int $roleId, string $roleType): array
    {
        $mappings = $this->masterMenuRoleMappingRepository->findByRoleId($roleId)
            ->filter(fn ($mapping) => $mapping->menu && $mapping->menu->web_type === $roleType);

        $items = $mappings->map(function ($mapping): array {
            $menu = $mapping->menu;
            $action = $this->mappingActions($mapping);

            return [
                'id' => $menu->id,
                'name' => $menu->title,
                'path' => $menu->path,
                'parent_id' => $menu->parent_id,
                'sequence' => $menu->order_index,
                'available_action' => $menu->available_actions ?? [],
                'action' => $action,
                'menu_code' => $menu->menu_code,
            ];
        })->values();

        $parentMenu = $items->whereNull('parent_id')->values();
        $childrenMenu = $items->whereNotNull('parent_id')->values();

        return $parentMenu
            ->filter(function (array $item) use ($childrenMenu): bool {
                $children = $childrenMenu->where('parent_id', $item['id']);

                if ($children->isNotEmpty()) {
                    return $children->contains(fn (array $child): bool => ! empty($child['action']));
                }

                return ! empty($item['action']);
            })
            ->map(function (array $item) use ($childrenMenu): array {
                $item['children'] = $childrenMenu
                    ->where('parent_id', $item['id'])
                    ->filter(fn (array $child): bool => ! empty($child['action']))
                    ->map(function (array $child): array {
                        unset($child['id'], $child['parent_id']);

                        return $child;
                    })
                    ->values()
                    ->all();

                unset($item['id'], $item['parent_id']);

                return $item;
            })
            ->values()
            ->all();
    }

    protected function buildEditableMenuTree(int $roleId, string $roleType): array
    {
        $menus = $this->masterMenuRepository->findByWebType($roleType)
            ->reject(fn ($menu): bool => $menu->path === '/portal-admin/role-management')
            ->values();

        $mappings = $this->masterMenuRoleMappingRepository->findByRoleId($roleId)->keyBy('menu_id');

        $items = $menus->map(function ($menu) use ($mappings): array {
            $mapping = $mappings->get($menu->id);

            return [
                'id' => $menu->id,
                'name' => $menu->title,
                'parent_id' => $menu->parent_id,
                'available_action' => $menu->available_actions ?? [],
                'action' => $mapping ? $this->mappingActions($mapping) : [],
            ];
        });

        $parentMenu = $items->whereNull('parent_id')->values();
        $childrenMenu = $items->whereNotNull('parent_id')->values();

        return $parentMenu
            ->map(function (array $item) use ($childrenMenu): array {
                $item['children'] = $childrenMenu
                    ->where('parent_id', $item['id'])
                    ->values()
                    ->all();

                return $item;
            })
            ->values()
            ->all();
    }

    protected function mappingActions(object $mapping): array
    {
        return collect([
            'view' => (bool) $mapping->can_view,
            'create' => (bool) $mapping->can_create,
            'edit' => (bool) $mapping->can_edit,
            'delete' => (bool) $mapping->can_delete,
            'approve' => (bool) $mapping->can_approve,
        ])
            ->filter()
            ->keys()
            ->values()
            ->all();
    }
}
