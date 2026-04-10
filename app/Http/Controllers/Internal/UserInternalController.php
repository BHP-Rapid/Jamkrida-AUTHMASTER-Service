<?php

namespace App\Http\Controllers\Internal;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\UserService;

class UserInternalController extends Controller
{
    public function __construct(
        protected UserService $userService,
    ) {
    }

    public function show(int $user)
    {
        $data = $this->userService->findById($user);

        if (! $data) {
            return ApiResponse::error(
                message: 'User tidak ditemukan.',
                status: 404,
                errors: [
                    'user' => ['Data user tidak ditemukan.'],
                ],
            );
        }

        return ApiResponse::success(
            data: $data,
            message: 'User berhasil diambil.',
        );
    }
}
