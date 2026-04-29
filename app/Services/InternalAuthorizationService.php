<?php

namespace App\Services;

use App\Repositories\MasterMenuRoleMappingRepository;
use App\Repositories\MasterRoleRepository;
use App\Repositories\UserMitraRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InternalAuthorizationService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserMitraRepository $userMitraRepository,
        protected MasterRoleRepository $masterRoleRepository,
        protected MasterMenuRoleMappingRepository $masterMenuRoleMappingRepository,
    ) {
    }

    public function getUserContext(int|string $userId): ?array
    {
        $user = $this->userRepository->findByUserId($userId);
        $authType = 'admin';

        if (! $user) {
            $user = $this->userMitraRepository->findByUserId((string) $userId);
            $authType = 'mitra';
        }

        if (! $user) {
            return null;
        }

        $roleId = $this->resolveRoleId($user);
        $role = $this->resolveRole($user, $roleId);
        $dataTenant = $this->resolveTenantContextForUser($user);
        $userClaim = $this->buildUserContextClaim($user, $dataTenant);

        return [
            'id' => $user->getKey(),
            'user_id' => $user->user_id ?? $user->getKey(),
            'auth_type' => $authType,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_id' => $roleId,
            'role_code' => $role?->role_code,
            'role_name' => $role?->role_name,
            'mitra_id' => $user->mitra_id ?? null,
            'mitra_name' => $dataTenant['mitra_alias'] ?? null,
            'tenant_id' => $dataTenant['tenant_id'] ?? null,
            'tenant_name' => $dataTenant['tenant_name'] ?? null,
            'status' => $user->status ?? null,
            'user' => $userClaim,
            ];
    }

    public function checkPermission(int|string $userId, int|string|null $menuIdentifier, string|array $actions = 'view'): ?array
    {
        $context = $this->getUserContext($userId);

        if (! $context) {
            return null;
        }

        $allowed = false;
        $normalizedActions = $this->normalizePermissionActions($actions);

        if ($context['role_id'] !== null && $menuIdentifier !== null && $menuIdentifier !== '') {
            $allowed = $this->masterMenuRoleMappingRepository->hasAnyPermission(
                $context['role_id'],
                $menuIdentifier,
                $normalizedActions,
            );
        }

        return [
            'allowed' => $allowed,
            'action' => implode(',', $normalizedActions),
            'actions' => $normalizedActions,
            'menu_identifier' => $menuIdentifier,
            'user' => $context,
        ];
    }

    public function checkRole(int|string $userId, array $roles): ?array
    {
        $context = $this->getUserContext($userId);

        if (! $context) {
            return null;
        }

        $normalizedRoles = array_values(array_filter(
            array_map(static fn (mixed $role): string => (string) $role, $roles),
            static fn (string $role): bool => $role !== '',
        ));

        $allowed = false;

        if (
            $context['role'] !== null
            && in_array((string) $context['role'], $normalizedRoles, true)
        ) {
            $allowed = true;
        }

        if (
            ! $allowed
            && $context['role_id'] !== null
            && in_array((string) $context['role_id'], $normalizedRoles, true)
        ) {
            $allowed = true;
        }

        if (
            ! $allowed
            && $context['role_code'] !== null
            && in_array((string) $context['role_code'], $normalizedRoles, true)
        ) {
            $allowed = true;
        }

        return [
            'allowed' => $allowed,
            'roles' => $normalizedRoles,
            'user' => $context,
        ];
    }

    protected function resolveRoleId(object $user): int|string|null
    {
        if (isset($user->role_id) && $user->role_id !== null && $user->role_id !== '') {
            return $user->role_id;
        }

        if (! isset($user->role) || $user->role === null || $user->role === '') {
            return null;
        }

        if (is_numeric($user->role)) {
            return $user->role;
        }

        return $this->masterRoleRepository->findByIdentifier((string) $user->role)?->getKey();
    }

    protected function resolveRole(object $user, int|string|null $roleId): ?object
    {
        if ($roleId !== null) {
            return $this->masterRoleRepository->findById($roleId);
        }

        if (! isset($user->role) || $user->role === null || $user->role === '') {
            return null;
        }

        return $this->masterRoleRepository->findByIdentifier((string) $user->role);
    }

    protected function normalizePermissionActions(string|array $actions): array
    {
        $items = is_array($actions) ? $actions : explode(',', $actions);

        $normalizedActions = array_values(array_filter(
            array_map(static fn (mixed $action): string => trim((string) $action), $items),
            static fn (string $action): bool => $action !== '',
        ));

        return $normalizedActions === [] ? ['view'] : $normalizedActions;
    }

    protected function buildUserContextClaim(object $user, ?array $dataTenant = null): array
    {
        $dataTenant ??= $this->resolveTenantContextForUser($user);

        return [
            'id' => $user->getKey(),
            'user_id' => $user->user_id ?? $user->getKey(),
            'mitra_id' => $user->mitra_id ?? null,
            'tenant_id' => $dataTenant['tenant_id'] ?? null,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }

    protected function resolveTenantContextForUser(object $user): array
    {
        $tenantId = $this->resolveTenantIdForUser($user);

        if ($tenantId === null) {
            return [
                'tenant_id' => null,
                'tenant_name' => null,
                'mitra_alias' => null,
            ];
        }

        if (
            ! Schema::hasTable('tenant_mitra')
            || ! Schema::hasColumn('tenant_mitra', 'tenant_id')
        ) {
            return [
                'tenant_id' => $tenantId,
                'tenant_name' => null,
                'mitra_alias' => null,
            ];
        }

        $selects = [];

        if (Schema::hasColumn('tenant_mitra', 'name')) {
            $selects[] = 'name as tenant_name';
        }

        if (Schema::hasColumn('tenant_mitra', 'alias')) {
            $selects[] = 'alias as mitra_alias';
        }

        $tenantRecord = $selects === []
            ? null
            : DB::table('tenant_mitra')
                ->where('tenant_id', $tenantId)
                ->first($selects);

        return [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantRecord->tenant_name ?? null,
            'mitra_alias' => $tenantRecord->mitra_alias ?? null,
        ];
    }

    protected function resolveTenantIdForUser(object $user): ?string
    {
        $tenantId = $user->tenant_id ?? null;

        if ($tenantId !== null && $tenantId !== '') {
            return (string) $tenantId;
        }

        $mitraId = $user->mitra_id ?? null;

        if ($mitraId === null || $mitraId === '') {
            return null;
        }

        if (
            ! Schema::hasTable('tenant_mitra')
            || ! Schema::hasColumn('tenant_mitra', 'tenant_id')
            || ! Schema::hasColumn('tenant_mitra', 'mitra_id')
        ) {
            return null;
        }

        $tenantId = DB::table('tenant_mitra')
            ->where('mitra_id', $mitraId)
            ->value('tenant_id');

        return $tenantId !== null && $tenantId !== '' ? (string) $tenantId : null;
    }
}
