<?php

namespace App\Repositories;

use App\Models\OTPVerification;
use Carbon\CarbonInterface;

class OTPVerificationRepository
{
    public function findValidLatestByUserId(int|string $userId): ?OTPVerification
    {
        return OTPVerification::query()
            ->where('user_id', $userId)
            ->where('valid_before', '>', now())
            ->latest()
            ->first();
    }

    public function findValidOtp(int|string $userId, string $otp): ?OTPVerification
    {
        return OTPVerification::query()
            ->where('user_id', $userId)
            ->where('otp', $otp)
            //bukak nanti waktu jam server dan api sudah sync ->where('valid_before', '>', now())
            ->latest()
            ->first();
    }

    public function deleteExpiredByUserId(int|string $userId): void
    {
        OTPVerification::query()
            ->where('user_id', $userId)
            ->where('valid_before', '<', now())
            ->delete();
    }

    public function create(array $payload): OTPVerification
    {
        return OTPVerification::query()->create($payload);
    }

    public function deleteById(int|string $id): void
    {
        OTPVerification::query()->whereKey($id)->delete();
    }
}
