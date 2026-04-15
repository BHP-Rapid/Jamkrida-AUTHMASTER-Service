<?php

namespace App\Services;

use App\Repositories\MitraRepository;
use App\Repositories\UserMitraRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PublicService
{
    public function __construct(
        protected MitraRepository $mitraRepository,
        protected UserRepository $userRepository,
        protected UserMitraRepository $userMitraRepository,
    ) {
    }

    public function index(string $ipAddress, ?string $userAgent = null): array
    {
        try {
            $key = 'bank-values-api:'.$ipAddress;
            RateLimiter::hit($key, 3600);

            $bankValues = Cache::remember('bank_values_public', 300, function () {
                return $this->mitraRepository->getPublicBankValues();
            });

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data bank berhasil diambil.',
                'data' => $bankValues,
            ];
        } catch (\Throwable $exception) {
            Log::error('Public bank values retrieval failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Terjadi kesalahan server',
            ];
        }
    }

    public function checkId(array $payload, string $ipAddress, ?string $userAgent = null): array
    {
        try {
            $key = 'check-id:'.$ipAddress;
            RateLimiter::hit($key, 3600);

            if (! empty($payload['user_id'])) {
                $userId = strip_tags(trim((string) $payload['user_id']));
                $result = $this->userMitraRepository->findPublicIdentityByUserId($userId);
            } elseif (! empty($payload['email'])) {
                $email = strip_tags(trim((string) $payload['email']));
                $result = $this->userRepository->findByEmail($email)?->only(['email', 'mitra_id']);
            } else {
                return [
                    'success' => false,
                    'status' => 400,
                    'message' => 'Either user_id or email is required',
                ];
            }

            if (! $result) {
                return [
                    'success' => false,
                    'status' => 200,
                    'message' => 'Data tidak ditemukan',
                    'data' => null,
                ];
            }

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data berhasil ditemukan.',
                'data' => $result,
            ];
        } catch (\Throwable $exception) {
            Log::error('Public check id failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Terjadi kesalahan server',
            ];
        }
    }
}
