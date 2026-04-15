<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internal\Concerns\RespondsWithServiceResult;
use App\Http\Requests\Role\RoleAccessRequest;
use App\Http\Requests\Role\RoleTableRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Services\UserRoleService;

class RoleInternalController extends Controller
{
    use RespondsWithServiceResult;

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
}
