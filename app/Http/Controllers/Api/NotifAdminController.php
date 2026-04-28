<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notif\CreateNotifAdminRequest;
use App\Http\Requests\Notif\GetMitraRecipientRequest;
use App\Http\Requests\Notif\GetNotifAdminRequest;
use App\Services\NotifAdminService;

class NotifAdminController extends Controller
{
    public function __construct(
        protected NotifAdminService $notifAdminService,
    ) {
    }

    public function index(GetNotifAdminRequest $request)
    {
        $result = $this->notifAdminService->index($request->validated());

        if (! $result['success']) {
            return ApiResponse::error(
                message: $result['message'],
                status: $result['status'],
                data: $result['data'] ?? null,
            );
        }

        $data = $result['data'];

        return ApiResponse::success(
            data: $data->items(),
            message: $result['message'],
            status: $result['status'],
            meta: [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ],
        );
    }

    public function getMitraRecipient(GetMitraRecipientRequest $request)
    {
        $result = $this->notifAdminService->getMitraRecipient($request->validated());

        if (! $result['success']) {
            return ApiResponse::error(
                message: $result['message'],
                status: $result['status'],
                data: $result['data'] ?? null,
            );
        }

        return ApiResponse::success(
            data: $result['data'] ?? null,
            message: $result['message'],
            status: $result['status'],
            meta: $result['meta'] ?? [],
        );
    }

    public function createNotifAdmin(CreateNotifAdminRequest $request)
    {
        $result = $this->notifAdminService->createNotifAdmin($request->validated());

        if (! $result['success']) {
            return ApiResponse::error(
                message: $result['message'],
                status: $result['status'],
                data: $result['data'] ?? null,
            );
        }

        return ApiResponse::success(
            data: $result['data'] ?? null,
            message: $result['message'],
            status: $result['status'],
        );
    }

    public function getById(int $id)
    {
        $result = $this->notifAdminService->getById($id);

        if (! $result['success']) {
            return ApiResponse::error(
                message: $result['message'],
                status: $result['status'],
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
