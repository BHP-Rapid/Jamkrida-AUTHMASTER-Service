<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleAccessRequest;
use App\Http\Requests\Role\RoleTableRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Services\UserRoleService;

class UserRoleController extends Controller
{
    public function __construct(
        protected UserRoleService $userRoleService,
    ) {
    }

    public function index()
    {
        return $this->respond($this->userRoleService->getCurrentRoleAccess(request()->user()));
    }

    public function getAllRoles(RoleTableRequest $request)
    {
        return $this->respond($this->userRoleService->getAllRoles($request->validated()));
    }

    public function getAccessByRole(RoleAccessRequest $request)
    {
        return $this->respond($this->userRoleService->getAccessByRole((int) $request->validated('id')));
    }

    public function updateRole(UpdateRoleRequest $request)
    {
        return $this->respond($this->userRoleService->updateRole($request->validated()));
    }

    public function getRoleByType(string $roleType)
    {
        return $this->respond($this->userRoleService->getRoleByType($roleType));
    }

    protected function respond(array $result)
    {
        if (! $result['success']) {
            return ApiResponse::error(
                message: $result['message'],
                status: $result['status'],
                errors: $result['errors'] ?? [],
                data: $result['data'] ?? null,
            );
        }

        return ApiResponse::success(
            data: $result['data'] ?? null,
            message: $result['message'],
            status: $result['status'],
        );
    }
}
