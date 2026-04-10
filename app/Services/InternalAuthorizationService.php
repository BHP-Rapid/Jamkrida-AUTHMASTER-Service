<?php

namespace App\Services;

use App\Repositories\MasterMenuRoleMappingRepository;
use App\Repositories\MasterRoleRepository;
use App\Repositories\UserMitraRepository;
use App\Repositories\UserRepository;

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
            'status' => $user->status ?? null,
        ];
    }

    public function checkPermission(int|string $userId, int|string|null $menuIdentifier, string $action = 'view'): ?array
    {
        $context = $this->getUserContext($userId);

        if (! $context) {
            return null;
        }

        $allowed = false;

        if ($context['role_id'] !== null && $menuIdentifier !== null && $menuIdentifier !== '') {
            $allowed = $this->masterMenuRoleMappingRepository->hasPermission(
                $context['role_id'],
                $menuIdentifier,
                $action,
            );
        }

        return [
            'allowed' => $allowed,
            'action' => $action,
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
}
