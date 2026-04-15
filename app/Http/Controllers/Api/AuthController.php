<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AdminLoginRequest;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\MitraLoginRequest;
use App\Http\Requests\User\RefreshTokenRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Requests\User\ResendResetPasswordRequest;
use App\Http\Requests\User\ValidateResetUrlRequest;
use App\Http\Requests\User\VerifyAdminOtpRequest;
use App\Http\Requests\User\VerifyMitraOtpRequest;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

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

    public function loginAdmin(AdminLoginRequest $request)
    {
        $result = $this->authService->loginAdmin($request->validated());

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

    public function verifyAdminOtp(VerifyAdminOtpRequest $request)
    {
        $result = $this->authService->verifyAdminOtp($request->validated());

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

    public function loginMitra(MitraLoginRequest $request)
    {
        $result = $this->authService->loginMitra($request->validated());

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

    public function verifyMitraOtp(VerifyMitraOtpRequest $request)
    {
        $result = $this->authService->verifyMitraOtp($request->validated());

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

    public function refreshToken(RefreshTokenRequest $request)
    {
        $result = $this->authService->refreshToken(
            $request->validated(),
            (string) $request->bearerToken(),
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
            data: $result['data'] ?? null,
            message: $result['message'],
            status: $result['status'],
        );
    }

    public function validateResetUrl(ValidateResetUrlRequest $request)
    {
        $result = $this->authService->validateResetUrl(
            (string) $request->validated('url_key'),
            (string) $request->validated('user_type'),
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
            data: $result['data'] ?? null,
            message: $result['message'],
            status: $result['status'],
        );
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $result = $this->authService->resetPassword($request->validated());

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

    public function resendResetPasswordEmail(ResendResetPasswordRequest $request)
    {
        $validated = $request->validated();

        if (
            $validated['user_type'] === 'admin'
            && empty($validated['email'])
        ) {
            return ApiResponse::error(
                message: 'Email tidak ditemukan',
                status: 404,
            );
        }

        if (
            $validated['user_type'] === 'mitra'
            && empty($validated['user_id'])
        ) {
            return ApiResponse::error(
                message: 'User ID tidak ditemukan',
                status: 404,
            );
        }

        $result = $this->authService->resendResetPasswordEmail($validated);

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
