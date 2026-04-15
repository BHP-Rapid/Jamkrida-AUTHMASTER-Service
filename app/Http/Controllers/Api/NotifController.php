<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notif\CountNotifRequest;
use App\Http\Requests\Notif\GetNotifRequest;
use App\Http\Requests\Notif\UpdateAllNotifRequest;
use App\Http\Requests\Notif\UpdateNotifRequest;
use App\Services\NotifService;

class NotifController extends Controller
{
    public function __construct(
        protected NotifService $notifService,
    ) {
    }

    public function getNotif(GetNotifRequest $request)
    {
        $result = $this->notifService->getNotif($request->validated());

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

    public function countNotif(CountNotifRequest $request)
    {
        $result = $this->notifService->countNotif($request->validated());

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

    public function update(UpdateNotifRequest $request)
    {
        $result = $this->notifService->update($request->validated());

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

    public function updateAllNotif(UpdateAllNotifRequest $request)
    {
        $result = $this->notifService->updateAllNotif($request->validated());

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
