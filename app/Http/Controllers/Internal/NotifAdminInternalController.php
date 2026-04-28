<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internal\Concerns\RespondsWithServiceResult;
use App\Http\Requests\Notif\CreateNotifAdminRequest;
use App\Http\Requests\Notif\GetMitraRecipientRequest;
use App\Http\Requests\Notif\GetNotifAdminRequest;
use App\Services\NotifAdminService;

class NotifAdminInternalController extends Controller
{
    use RespondsWithServiceResult;

    public function __construct(
        protected NotifAdminService $notifAdminService,
    ) {
    }

    public function index(GetNotifAdminRequest $request)
    {
        $result = $this->notifAdminService->index($request->validated());

        if (! $result['success']) {
            return $this->errorResponse(
                message: $result['message'],
                status: $result['status'],
                data: $result['data'] ?? null,
            );
        }

        $data = $result['data'];

        return $this->successResponse(
            data: $data->items(),
            message: $result['message'],
            status: $result['status'],
            extra: [
                'meta' => [
                    'total' => $data->total(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                ],
            ],
        );
    }

    public function getMitraRecipient(GetMitraRecipientRequest $request)
    {
        $result = $this->notifAdminService->getMitraRecipient($request->validated());

        if (! $result['success']) {
            return $this->errorResponse(
                message: $result['message'],
                status: $result['status'],
                data: $result['data'] ?? null,
            );
        }

        return $this->successResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            status: $result['status'],
            extra: [
                'meta' => $result['meta'] ?? [],
            ],
        );
    }

    public function createNotifAdmin(CreateNotifAdminRequest $request)
    {
        $result = $this->notifAdminService->createNotifAdmin($request->validated());

        if (! $result['success']) {
            return $this->errorResponse(
                message: $result['message'],
                status: $result['status'],
                data: $result['data'] ?? null,
            );
        }

        return $this->successResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            status: $result['status'],
        );
    }

    public function getById(int $id)
    {
        $result = $this->notifAdminService->getById($id);

        if (! $result['success']) {
            return $this->errorResponse(
                message: $result['message'],
                status: $result['status'],
                data: $result['data'] ?? null,
            );
        }

        return $this->successResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            status: $result['status'],
        );
    }
}
