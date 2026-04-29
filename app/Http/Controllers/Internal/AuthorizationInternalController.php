<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internal\Concerns\RespondsWithServiceResult;
use App\Services\InternalAuthorizationService;
use Illuminate\Http\Request;

class AuthorizationInternalController extends Controller
{
    use RespondsWithServiceResult;

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
            return $this->errorResponse(
                message: 'User context tidak ditemukan.',
                status: 404,
                errors: [
                    'user_id' => ['Data user tidak ditemukan.'],
                ],
            );
        }

        return $this->successResponse(
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
            'actions' => ['nullable', 'array'],
            'actions.*' => ['required', 'string'],
        ]);

        $resolvedUserId = $this->resolveForwardedUserId($request, $validated['user_id'] ?? null);

        if ($resolvedUserId instanceof \Illuminate\Http\JsonResponse) {
            return $resolvedUserId;
        }

        $menuIdentifier = $validated['menu_code'] ?? $validated['menu_id'] ?? null;
        $result = $this->internalAuthorizationService->checkPermission(
            $resolvedUserId,
            $menuIdentifier,
            $validated['actions'] ?? (string) ($validated['action'] ?? 'view'),
        );

        if (! $result) {
            return $this->errorResponse(
                message: 'User context tidak ditemukan.',
                status: 404,
                errors: [
                    'user_id' => ['Data user tidak ditemukan.'],
                ],
            );
        }

        return $this->successResponse(
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
            return $this->errorResponse(
                message: 'User context tidak ditemukan.',
                status: 404,
                errors: [
                    'user_id' => ['Data user tidak ditemukan.'],
                ],
            );
        }

        return $this->successResponse(
            data: $result,
            message: 'Role check berhasil diproses.',
        );
    }

    protected function resolveForwardedUserId(Request $request, int|string|null $requestedUserId): int|string|\Illuminate\Http\JsonResponse
    {
        $forwardedUserId = (string) $request->attributes->get('forwarded_user_id', '');

        if ($forwardedUserId === '') {
            return $this->errorResponse(
                message: 'Unauthorized: forwarded user context missing.',
                status: 401,
            );
        }

        if ($requestedUserId !== null && (string) $requestedUserId !== '' && (string) $requestedUserId !== $forwardedUserId) {
            return $this->errorResponse(
                message: 'Forbidden: requested user does not match forwarded token.',
                status: 403,
            );
        }

        return $forwardedUserId;
    }
}
