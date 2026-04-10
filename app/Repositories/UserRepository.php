<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserRepository
{
    public function findById(int|string $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    public function findByUserId(int|string $userId): ?User
    {
        $query = User::query();

        if (Schema::hasColumn((new User())->getTable(), 'user_id')) {
            return $query->where('user_id', $userId)->first();
        }

        return $query->find($userId);
    }

    public function incrementLoginAttempts(User $user, int $maxAttempts, int $suspendSeconds): int
    {
        if (! Schema::hasColumn($user->getTable(), 'login_attempts')) {
            return 0;
        }

        $user->login_attempts = ((int) $user->login_attempts) + 1;
        $remainingAttempts = max($maxAttempts - (int) $user->login_attempts, 0);

        if (
            $maxAttempts > 0
            && $user->login_attempts >= $maxAttempts
            && Schema::hasColumn($user->getTable(), 'suspend_until')
        ) {
            $user->suspend_until = now()->addSeconds($suspendSeconds);
            $user->login_attempts = 0;
            $remainingAttempts = 0;
        }

        $user->save();

        return $remainingAttempts;
    }

    public function resetLoginAttempts(User $user): void
    {
        $dirty = false;

        if (Schema::hasColumn($user->getTable(), 'login_attempts')) {
            $user->login_attempts = 0;
            $dirty = true;
        }

        if (Schema::hasColumn($user->getTable(), 'suspend_until')) {
            $user->suspend_until = null;
            $dirty = true;
        }

        if (Schema::hasColumn($user->getTable(), 'last_login')) {
            $user->last_login = now();
            $dirty = true;
        }

        if ($dirty) {
            $user->save();
        }
    }
}
