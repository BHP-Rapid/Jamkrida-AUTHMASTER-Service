<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository,
    ) {
    }

    public function findById(int|string $id): ?array
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password' => $user->password,
        ];
    }
}
