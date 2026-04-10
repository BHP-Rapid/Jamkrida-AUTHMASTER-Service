<?php

namespace App\Http\Controllers\Internal;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\InternalAuthorizationService;
use Illuminate\Http\Request;

class AuthorizationInternalController extends Controller
{
    public function __construct(
        protected InternalAuthorizationService $internalAuthorizationService,
    ) {
    }

    public function context(Request $request, string $userId)
    {
        $resolvedUserId = $this->resolveForwardedUserId($request, $userId);

        if ($resolvedUserId instanceof \Illuminate\Http\JsonResponse) {
            return $resolvedUserId;
        }

        $data = $this->internalAuthorizationService->getUserContext($resolvedUserId);

        if (! $data) {
            return ApiResponse::error(
                message: 'User context tidak ditemukan.',
                status: 404,
                errors: [
                    'user_id' => ['Data user tidak ditemukan.'],
                ],
            );
        }

        return ApiResponse::success(
            data: $data,
            message: 'User context berhasil diambil.',
        );
    }

    public function checkPermission(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['nullable'],
            'menu_code' => ['nullable', 'string'],
            'menu_id' => ['nullable'],
            'action' => ['nullable', 'string'],
        ]);

        $resolvedUserId = $this->resolveForwardedUserId($request, $validated['user_id'] ?? null);

        if ($resolvedUserId instanceof \Illuminate\Http\JsonResponse) {
            return $resolvedUserId;
        }

        $menuIdentifier = $validated['menu_code'] ?? $validated['menu_id'] ?? null;
        $result = $this->internalAuthorizationService->checkPermission(
            $resolvedUserId,
            $menuIdentifier,
            (string) ($validated['action'] ?? 'view'),
        );

        if (! $result) {
            return ApiResponse::error(
                message: 'User context tidak ditemukan.',
                status: 404,
                errors: [
                    'user_id' => ['Data user tidak ditemukan.'],
                ],
            );
        }

        return ApiResponse::success(
            data: $result,
            message: 'Permission check berhasil diproses.',
        );
    }

    public function checkRole(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['nullable'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required'],
        ]);

        $resolvedUserId = $this->resolveForwardedUserId($request, $validated['user_id'] ?? null);

        if ($resolvedUserId instanceof \Illuminate\Http\JsonResponse) {
            return $resolvedUserId;
        }

        $result = $this->internalAuthorizationService->checkRole(
            $resolvedUserId,
            $validated['roles'],
        );

        if (! $result) {
            return ApiResponse::error(
                message: 'User context tidak ditemukan.',
                status: 404,
                errors: [
                    'user_id' => ['Data user tidak ditemukan.'],
                ],
            );
        }

        return ApiResponse::success(
            data: $result,
            message: 'Role check berhasil diproses.',
        );
    }

    protected function resolveForwardedUserId(Request $request, int|string|null $requestedUserId): int|string|\Illuminate\Http\JsonResponse
    {
        $forwardedUserId = (string) $request->attributes->get('forwarded_user_id', '');

        if ($forwardedUserId === '') {
            return ApiResponse::error(
                message: 'Unauthorized: forwarded user context missing.',
                status: 401,
            );
        }

        if ($requestedUserId !== null && (string) $requestedUserId !== '' && (string) $requestedUserId !== $forwardedUserId) {
            return ApiResponse::error(
                message: 'Forbidden: requested user does not match forwarded token.',
                status: 403,
            );
        }

        return $forwardedUserId;
    }
}
