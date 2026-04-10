<?php

namespace App\Repositories;

use App\Models\UserMitra;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserMitraRepository
{
    public function findById(int|string $id): ?UserMitra
    {
        return UserMitra::query()->find($id);
    }

    public function findByUserId(string $userId): ?UserMitra
    {
        return UserMitra::query()
            ->where('user_id', $userId)
            ->first();
    }

    public function findForLoginByUserId(string $userId): ?UserMitra
    {
        return UserMitra::query()
            ->leftJoin('tenant_mitra', 'tenant_mitra.mitra_id', '=', 'user_mitra.mitra_id')
            ->where('user_mitra.user_id', $userId)
            ->select(
                'user_mitra.*',
                'tenant_mitra.is_conventional',
                'tenant_mitra.is_syariah',
                DB::raw("CASE
                    WHEN tenant_mitra.is_conventional = 1 AND tenant_mitra.is_syariah = 0 THEN 'conventional'
                    WHEN tenant_mitra.is_syariah = 1 AND tenant_mitra.is_conventional = 0 THEN 'syariah'
                    WHEN tenant_mitra.is_conventional = 1 AND tenant_mitra.is_syariah = 1 THEN 'both'
                    ELSE 'unknown'
                END as tipe_metod_mitra")
            )
            ->first();
    }

    public function incrementLoginAttempts(UserMitra $user, int $maxAttempts, int $suspendSeconds): int
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

    public function resetLoginAttempts(UserMitra $user): void
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
