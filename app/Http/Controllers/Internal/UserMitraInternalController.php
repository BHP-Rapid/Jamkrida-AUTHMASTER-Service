<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internal\Concerns\RespondsWithServiceResult;
use App\Http\Requests\User\ChangeMitraPasswordRequest;
use App\Http\Requests\User\CreateMitraUserRequest;
use App\Http\Requests\User\MitraRegisterRequest;
use App\Http\Requests\User\MitraUserTableRequest;
use App\Http\Requests\User\UpdateMitraUserApprovalRequest;
use App\Http\Requests\User\UpdateMitraUserRequest;
use App\Http\Requests\User\UpdateMitraUserStatusRequest;
use App\Services\UserMitraService;
use Illuminate\Http\Request;

class UserMitraInternalController extends Controller
{
    use RespondsWithServiceResult;

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
            return $this->errorResponse($result['message'], $result['status']);
        }

        return $this->successResponse(
            data: $result['data'],
            message: $result['message'] ?? 'Users retrieved successfully',
            status: $result['status'],
        );
    }

    public function getDataVerification(Request $request)
    {
        $result = $this->userMitraService->getDataVerification($request->user());

        if (! $result['success']) {
            return $this->errorResponse($result['message'], $result['status']);
        }

        return $this->successResponse(
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
        return $this->respond($this->userMitraService->storeRegister($request->validated()));
    }

    public function uploadExcel(Request $request)
    {
        $result = $this->userMitraService->uploadExcel($request->all());

        if (! $result['success'] && ($result['status'] ?? 200) !== 200) {
            return $this->errorResponse($result['message'], $result['status']);
        }

        return $this->successResponse(
            data: $result['data']['data'] ?? [],
            message: $result['message'],
            status: $result['status'],
            extra: [
                'inserted_count' => $result['data']['inserted_count'] ?? 0,
                'failed' => $result['data']['failed'] ?? [],
            ],
        );
    }

    public function getDataById(string $userId)
    {
        $result = $this->userMitraService->getDataById($userId);

        if (! $result['success']) {
            return $this->errorResponse(
                message: $result['message'],
                status: $result['status'],
            );
        }

        return $this->successResponse(
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
}
