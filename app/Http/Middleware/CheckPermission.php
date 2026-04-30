<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\MenuPermissionHelper;
use App\Repositories\MasterMenuRoleMappingRepository;
use App\Repositories\MasterRoleRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        protected MenuPermissionHelper $menuPermissionHelper,
        protected MasterRoleRepository $masterRoleRepository,
        protected MasterMenuRoleMappingRepository $masterMenuRoleMappingRepository,
    ) {
    }

    public function handle(Request $request, Closure $next, string $menuIdentifier, string ...$actions): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(
                message: 'Unauthorized: user not found.',
                status: 401,
            );
        }

        $roleId = $this->resolveRoleId($user);

        if ($roleId === null) {
            return ApiResponse::error(
                message: 'Forbidden: role mapping not found.',
                status: 403,
            );
        }

        if ($this->isPermissionExpression($menuIdentifier)) {
            $expression = $this->buildPermissionExpression($menuIdentifier, $actions);

            return $this->handlePermissionExpression($request, $next, $user, $roleId, $expression);
        }

        $menuId = $this->menuPermissionHelper->resolveMenuId($menuIdentifier);

        if ($menuId === null) {
            return ApiResponse::error(
                message: 'Forbidden: menu mapping not found.',
                status: 403,
            );
        }

        if ($actions === []) {
            $actions = ['view'];
        }

        if (! $this->masterMenuRoleMappingRepository->hasAnyPermission($roleId, $menuId, $actions)) {
            return ApiResponse::error(
                message: 'Forbidden: insufficient permission.',
                status: 403,
            );
        }

        return $next($request);
    }

    protected function handlePermissionExpression(Request $request, Closure $next, object $user, int|string $roleId, string $expression): Response
    {
        $hasApplicableSpec = false;
        $hasResolvedMenu = false;

        foreach ($this->parsePermissionExpression($expression) as $spec) {
            if ($spec['role'] !== null && ! $this->roleMatches($user, $roleId, $spec['role'])) {
                continue;
            }

            $hasApplicableSpec = true;

            $menuId = $this->menuPermissionHelper->resolveMenuId($spec['menu']);

            if ($menuId === null) {
                continue;
            }

            $hasResolvedMenu = true;

            if ($this->masterMenuRoleMappingRepository->hasAnyPermission($roleId, $menuId, $spec['actions'])) {
                return $next($request);
            }
        }

        if ($hasApplicableSpec && ! $hasResolvedMenu) {
            return ApiResponse::error(
                message: 'Forbidden: menu mapping not found.',
                status: 403,
            );
        }

        return ApiResponse::error(
            message: 'Forbidden: insufficient permission.',
            status: 403,
        );
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

    protected function isPermissionExpression(string $value): bool
    {
        return str_contains($value, '=');
    }

    protected function buildPermissionExpression(string $menuIdentifier, array $actions): string
    {
        if ($actions === []) {
            return $menuIdentifier;
        }

        return $menuIdentifier.','.implode(',', $actions);
    }

    protected function parsePermissionExpression(string $expression): array
    {
        $specs = [];

        foreach (explode('|', $expression) as $rawSpec) {
            $rawSpec = trim($rawSpec);

            if ($rawSpec === '') {
                continue;
            }

            $roleIdentifier = null;
            $permissionSpec = $rawSpec;

            if (str_contains($permissionSpec, '=')) {
                [$roleIdentifier, $permissionSpec] = explode('=', $permissionSpec, 2);
                $roleIdentifier = trim($roleIdentifier);
            }

            [$menuIdentifier, $actionExpression] = array_pad(explode(':', $permissionSpec, 2), 2, 'view');

            $menuIdentifier = trim($menuIdentifier);

            if ($menuIdentifier === '') {
                continue;
            }

            $actions = $this->normalizeActions(preg_split('/[,+]/', $actionExpression) ?: []);

            $specs[] = [
                'role' => $roleIdentifier !== '' ? $roleIdentifier : null,
                'menu' => $menuIdentifier,
                'actions' => $actions,
            ];
        }

        return $specs;
    }

    protected function normalizeActions(array $actions): array
    {
        $normalizedActions = array_values(array_filter(
            array_map(static fn (mixed $action): string => trim((string) $action), $actions),
            static fn (string $action): bool => $action !== '',
        ));

        if ($normalizedActions === []) {
            return ['view'];
        }

        return $normalizedActions;
    }

    protected function roleMatches(object $user, int|string $roleId, string $roleIdentifier): bool
    {
        $role = $this->masterRoleRepository->findById($roleId);

        $userRoleIdentifiers = array_filter([
            isset($user->role) ? (string) $user->role : null,
            isset($user->role_id) ? (string) $user->role_id : null,
            (string) $roleId,
            $role?->role_code,
            $role?->role_name,
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        return in_array($roleIdentifier, $userRoleIdentifiers, true);
    }
}
