<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangeMitraPasswordRequest;
use App\Http\Requests\User\CreateMitraUserRequest;
use App\Http\Requests\User\MitraRegisterRequest;
use App\Http\Requests\User\MitraUserTableRequest;
use App\Http\Requests\User\UpdateMitraUserApprovalRequest;
use App\Http\Requests\User\UpdateMitraUserRequest;
use App\Http\Requests\User\UpdateMitraUserStatusRequest;
use App\Services\UserMitraService;
use Illuminate\Http\Request;

class UserMitraController extends Controller
{
    public function __construct(
        protected UserMitraService $userMitraService,
    ) {
    }

    public function index()
    {
        return $this->respond($this->userMitraService->index());
    }

    public function getUsersByRole(MitraUserTableRequest $request)
    {
        $result = $this->userMitraService->getUsersByRole($request->validated());

        if (! $result['success']) {
            return ApiResponse::error($result['message'], $result['status']);
        }

        $data = $result['data'];

        return ApiResponse::success(
            data: $data->items(),
            message: $result['message'] ?? 'Users retrieved successfully',
            status: $result['status'],
            meta: [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ],
        );
    }

    public function getDataVerification(Request $request)
    {
        $result = $this->userMitraService->getDataVerification($request->user());

        if (! $result['success']) {
            return ApiResponse::error($result['message'], $result['status']);
        }

        return ApiResponse::success(
            data: $result['data'],
            message: $result['message'] ?? 'Data verification retrieved successfully',
            status: $result['status'],
        );
    }

    public function store(CreateMitraUserRequest $request)
    {
        return $this->respond($this->userMitraService->store(
            $request->user(),
            $request->validated(),
        ));
    }

    public function storeRegister(MitraRegisterRequest $request)
    {
        return $this->respond($this->userMitraService->storeRegister(
            $request->validated(),
        ));
    }

    public function uploadExcel(Request $request)
    {
        $result = $this->userMitraService->uploadExcel($request->all());

        if (! $result['success'] && ($result['status'] ?? 200) !== 200) {
            return ApiResponse::error($result['message'], $result['status']);
        }

        return ApiResponse::success(
            data: $result['data']['data'] ?? [],
            message: $result['message'],
            status: $result['status'],
            extra: [
                'success' => $result['success'],
                'inserted_count' => $result['data']['inserted_count'] ?? 0,
                'failed' => $result['data']['failed'] ?? [],
            ],
        );
    }

    public function getDataById(string $userId)
    {
        $result = $this->userMitraService->getDataById($userId);

        if (! $result['success']) {
            return ApiResponse::error(
                message: $result['message'],
                status: $result['status'],
            );
        }

        return ApiResponse::success(
            data: $result['data'],
            message: $result['message'] ?? 'User mitra berhasil diambil',
            status: $result['status'],
        );
    }

    public function updateByUserId(UpdateMitraUserRequest $request, string $userId)
    {
        return $this->respond($this->userMitraService->updateByUserId(
            $request->user(),
            $userId,
            $request->validated(),
        ));
    }

    public function updateStatus(UpdateMitraUserStatusRequest $request, string $userId)
    {
        return $this->respond($this->userMitraService->updateStatus(
            $request->user(),
            $userId,
            (string) $request->validated('status'),
        ));
    }

    public function updateStatusApproval(UpdateMitraUserApprovalRequest $request, string $userId)
    {
        return $this->respond($this->userMitraService->updateStatusApproval(
            $request->user(),
            $userId,
            (string) $request->validated('statusApproval'),
        ));
    }

    public function changePassword(ChangeMitraPasswordRequest $request)
    {
        return $this->respond($this->userMitraService->changePassword(
            $request->user(),
            $request->validated(),
        ));
    }

    public function deleteUser(string $userId)
    {
        return $this->respond($this->userMitraService->deleteUser(
            request()->user(),
            $userId,
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
