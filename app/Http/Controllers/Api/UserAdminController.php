<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AdminRegisterRequest;
use App\Http\Requests\User\AdminUserTableRequest;
use App\Http\Requests\User\ChangeAdminPasswordRequest;
use App\Http\Requests\User\UpdateAdminApprovalRequest;
use App\Http\Requests\User\UpdateAdminRequest;
use App\Http\Requests\User\UpdateAdminStatusRequest;
use App\Services\UserAdminService;

class UserAdminController extends Controller
{
    public function __construct(
        protected UserAdminService $userAdminService,
    ) {
    }

    public function index()
    {
        return $this->respond($this->userAdminService->index());
    }

    public function getUsersByRole(AdminUserTableRequest $request)
    {
        return $this->respond($this->userAdminService->getUsersByRole(
            $request->user(),
            $request->validated(),
        ));
    }

    public function getDataVerification(AdminUserTableRequest $request)
    {
        $result = $this->userAdminService->getDataVerification(
            $request->user(),
            $request->validated(),
        );

        if (! $result['success']) {
            return ApiResponse::error(
                message: $result['message'],
                status: $result['status'],
                errors: $result['errors'] ?? [],
                data: $result['data'] ?? null,
            );
        }

        return ApiResponse::success(
            data: $result['data']['data'] ?? [],
            message: $result['message'] ?? 'Data verification retrieved successfully',
            status: $result['status'],
            meta: [
                'total' => $result['data']['total'] ?? 0,
                'per_page' => $result['data']['per_page'] ?? 0,
                'current_page' => $result['data']['current_page'] ?? 1,
                'last_page' => $result['data']['last_page'] ?? 1,
            ],
        );
    }

    public function storeRegister(AdminRegisterRequest $request)
    {
        return $this->respond($this->userAdminService->storeRegister(
            $request->validated(),
        ));
    }

    public function getDataById(string $userId)
    {
        return $this->respond($this->userAdminService->getDataById($userId));
    }

    public function updateAdminByUserId(UpdateAdminRequest $request, string $userId)
    {
        return $this->respond($this->userAdminService->updateAdminByUserId(
            $request->user(),
            $userId,
            $request->validated(),
        ));
    }

    public function updateStatusApproval(UpdateAdminApprovalRequest $request, string $userId)
    {
        return $this->respond($this->userAdminService->updateStatusApproval(
            $request->user(),
            $userId,
            (string) $request->validated('statusApproval'),
        ));
    }

    public function updateStatus(UpdateAdminStatusRequest $request, string $userId)
    {
        return $this->respond($this->userAdminService->updateStatus(
            $request->user(),
            $userId,
            (string) $request->validated('status'),
        ));
    }

    public function changePassword(ChangeAdminPasswordRequest $request)
    {
        return $this->respond($this->userAdminService->changePassword(
            $request->user(),
            $request->validated(),
        ));
    }

    public function deleteUser(string $userId)
    {
        return $this->respond($this->userAdminService->deleteUser(
            request()->user(),
            $userId,
        ));
    }

    public function getRoleList()
    {
        return $this->respond($this->userAdminService->getRoleList(
            request()->user(),
        ));
    }

    public function getAdminMitraList()
    {
        return $this->respond($this->userAdminService->getAdminMitraList(
            request()->user(),
        ));
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
