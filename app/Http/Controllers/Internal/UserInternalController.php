<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internal\Concerns\RespondsWithServiceResult;
use App\Services\UserService;

class UserInternalController extends Controller
{
    use RespondsWithServiceResult;

    public function __construct(
        protected UserService $userService,
    ) {
    }

    public function show(int $user)
    {
        $data = $this->userService->findById($user);

        if (! $data) {
            return $this->errorResponse(
                message: 'User tidak ditemukan.',
                status: 404,
                errors: [
                    'user' => ['Data user tidak ditemukan.'],
                ],
            );
        }

        return $this->successResponse(
            data: $data,
            message: 'User berhasil diambil.',
        );
    }
}
