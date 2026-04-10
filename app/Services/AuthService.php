<?php

namespace App\Services;

use App\Mail\SendOtpToMitra;
use App\Repositories\OTPVerificationRepository;
use App\Repositories\SettingProductDetailRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserMitraRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserMitraRepository $userMitraRepository,
        protected OTPVerificationRepository $otpVerificationRepository,
        protected SettingProductDetailRepository $settingProductDetailRepository,
    ) {
    }

    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail((string) ($credentials['email'] ?? ''));

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan.',
                'status' => 404,
                'errors' => [
                    'email' => ['Email tidak terdaftar.'],
                ],
            ];
        }

        $hashedPassword = (string) $user->password;

        if ($hashedPassword === '' || ! Hash::check((string) ($credentials['password'] ?? ''), $hashedPassword)) {
            return [
                'success' => false,
                'message' => 'Password tidak valid.',
                'status' => 422,
                'errors' => [
                    'password' => ['Password yang diberikan tidak sesuai.'],
                ],
            ];
        }

        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'status' => 200,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ];
    }

    public function loginAdmin(array $credentials): array
    {
        $user = $this->userRepository->findByEmail((string) ($credentials['email'] ?? ''));

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan, tidak aktif, atau belum disetujui',
                'status' => 401,
            ];
        }

        $status = Str::lower((string) ($user->status ?? 'active'));
        $statusApproval = Str::lower((string) ($user->status_approval ?? 'approved'));
        $isDelete = (bool) ($user->is_delete ?? false);

        if ($statusApproval === 'rejected' && $status === 'inactive' && $isDelete) {
            return [
                'success' => false,
                'message' => 'Akun Anda Sudah Dihapus. Silahkan hubungi administrator',
                'status' => 401,
            ];
        }

        if ($status !== 'active') {
            return [
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Silahkan hubungi administrator',
                'status' => 401,
            ];
        }

        if ($statusApproval !== 'approved') {
            return [
                'success' => false,
                'message' => 'Akun Anda belum disetujui. Silahkan hubungi administrator',
                'status' => 401,
            ];
        }

        if ($user->suspend_until && now()->lt($user->suspend_until)) {
            $remaining = now()->diffInSeconds($user->suspend_until);

            return [
                'success' => false,
                'message' => 'Akun Anda terkunci sementara, coba lagi dalam '.gmdate('i:s', $remaining).' menit.',
                'status' => 423,
                'data' => [
                    'suspend' => true,
                ],
            ];
        }

        $maxAttempts = $this->settingProductDetailRepository->getIntValue(
            'MAX_FAILED_LOGIN_ATTEMP_GENERAL_SETTINGS',
            3
        );
        $suspendSeconds = $this->settingProductDetailRepository->getIntValue(
            'FAILED_LOGIN_SUSPENDED_GENERAL_SETTINGS',
            60
        );

        if (! Hash::check((string) ($credentials['password'] ?? ''), (string) $user->password)) {
            $remainingAttempts = $this->userRepository->incrementLoginAttempts(
                $user,
                $maxAttempts,
                $suspendSeconds
            );

            return [
                'success' => false,
                'message' => 'User ID atau password salah Hanya sisa '.$remainingAttempts.' Percobaan Login',
                'status' => 401,
            ];
        }

        $this->userRepository->resetLoginAttempts($user);

        $otpDuration = $this->settingProductDetailRepository->getIntValue(
            'OTP_DURATION_GENERAL_SETTINGS',
            300
        );
        $resentOtp = $this->settingProductDetailRepository->getIntValue(
            'RESENT_OTP_INTERVAL_GENERAL_SETTINGS',
            60
        );

        $userIdentifier = $user->user_id ?? $user->getKey();
        $otpRecord = $this->otpVerificationRepository->findValidLatestByUserId($userIdentifier);

        if ($otpRecord) {
            return [
                'success' => true,
                'message' => 'OTP sudah dikirim ke email Anda. Silakan cek email Anda.',
                'status' => 200,
                'data' => [
                    'user_id' => $userIdentifier,
                    'email' => $user->email,
                    'role' => $user->role,
                    'resentOTP' => $resentOtp,
                ],
            ];
        }

        $otp = random_int(10000, 99999);

        $this->otpVerificationRepository->deleteExpiredByUserId($userIdentifier);
        $this->otpVerificationRepository->create([
            'user_id' => $userIdentifier,
            'email' => $user->email,
            'otp' => (string) $otp,
            'valid_before' => now()->addSeconds($otpDuration),
        ]);

        Mail::to($user->email)->send(new SendOtpToMitra($otp, $userIdentifier));

        return [
            'success' => true,
            'message' => 'OTP telah dikirim ke email Anda.',
            'status' => 200,
            'data' => [
                'user_id' => $userIdentifier,
                'email' => $user->email,
                'role' => $user->role,
                'resentOTP' => $resentOtp,
            ],
        ];
    }

    public function verifyAdminOtp(array $payload): array
    {
        $user = $this->userRepository->findByUserId($payload['user_id']);

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan.',
                'status' => 404,
            ];
        }

        $otpRecord = $this->otpVerificationRepository->findValidOtp(
            $payload['user_id'],
            (string) $payload['otp']
        );

        if (! $otpRecord) {
            return [
                'success' => false,
                'message' => 'OTP tidak valid atau sudah kadaluarsa.',
                'status' => 422,
                'errors' => [
                    'otp' => ['OTP tidak valid atau sudah kadaluarsa.'],
                ],
            ];
        }

        $token = JWTAuth::fromUser($user);
        $this->otpVerificationRepository->deleteById($otpRecord->getKey());

        return [
            'success' => true,
            'message' => 'Verifikasi OTP berhasil.',
            'status' => 200,
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => (int) config('jwt.ttl', 60) * 60,
                'user' => [
                    'id' => $user->id,
                    'user_id' => $user->user_id ?? $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ];
    }

    public function loginMitra(array $credentials): array
    {
        try {
            DB::beginTransaction();

            $user = $this->userMitraRepository->findForLoginByUserId((string) ($credentials['user_id'] ?? ''));

            if (! $user) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat login. Silahkan hubungi administrator',
                    'status' => 401,
                ];
            }

            $status = Str::lower((string) ($user->status ?? ''));
            $statusApproval = Str::lower((string) ($user->statusApproval ?? ''));
            $isDelete = (bool) ($user->is_delete ?? false);

            if ($statusApproval === 'rejected' && $status === 'inactive' && $isDelete) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Akun Anda Sudah Dihapus. Silahkan hubungi administrator',
                    'status' => 401,
                ];
            }

            if ($status !== 'active') {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Akun Anda tidak aktif. Silahkan hubungi administrator',
                    'status' => 401,
                ];
            }

            if ($statusApproval !== 'approved') {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Akun Anda belum disetujui. Silahkan hubungi administrator',
                    'status' => 401,
                ];
            }

            if ($user->suspend_until && now()->lt($user->suspend_until)) {
                $remaining = now()->diffInSeconds($user->suspend_until);
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Akun Anda terkunci sementara, coba lagi dalam '.gmdate('i:s', $remaining).' menit.',
                    'status' => 423,
                    'data' => [
                        'suspend' => true,
                    ],
                ];
            }

            $otpDuration = $this->settingProductDetailRepository->getIntValue(
                'OTP_DURATION_GENERAL_SETTINGS',
                300
            );
            $resentOtp = $this->settingProductDetailRepository->getIntValue(
                'RESENT_OTP_INTERVAL_GENERAL_SETTINGS',
                60
            );
            $maxAttempts = $this->settingProductDetailRepository->getIntValue(
                'MAX_FAILED_LOGIN_ATTEMP_GENERAL_SETTINGS',
                3
            );
            $suspendSeconds = $this->settingProductDetailRepository->getIntValue(
                'FAILED_LOGIN_SUSPENDED_GENERAL_SETTINGS',
                60
            );

            if (! Hash::check((string) ($credentials['password'] ?? ''), (string) $user->password)) {
                $remainingAttempts = $this->userMitraRepository->incrementLoginAttempts(
                    $user,
                    $maxAttempts,
                    $suspendSeconds
                );

                DB::commit();

                return [
                    'success' => false,
                    'message' => 'User ID atau password salah Hanya sisa '.$remainingAttempts.' Percobaan Login',
                    'status' => 401,
                ];
            }

            $this->userMitraRepository->resetLoginAttempts($user);

            $otpRecord = $this->otpVerificationRepository->findValidLatestByUserId($user->user_id);

            if ($otpRecord) {
                DB::commit();

                return [
                    'success' => true,
                    'message' => 'OTP sudah dikirim ke email Anda. Silakan cek email Anda.',
                    'status' => 200,
                    'data' => [
                        'user_id' => $user->user_id,
                        'email' => $user->email,
                        'role' => $user->role,
                        'resentOTP' => $resentOtp,
                    ],
                ];
            }

            $otp = random_int(10000, 99999);

            $this->otpVerificationRepository->deleteExpiredByUserId($user->user_id);
            $this->otpVerificationRepository->create([
                'user_id' => $user->user_id,
                'email' => $user->email,
                'otp' => (string) $otp,
                'valid_before' => now()->addSeconds($otpDuration),
            ]);

            Mail::to($user->email)->send(new SendOtpToMitra($otp, $user->user_id));

            DB::commit();

            return [
                'success' => true,
                'message' => 'OTP telah dikirim ke email Anda.',
                'status' => 200,
                'data' => [
                    'user_id' => $user->user_id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'resentOTP' => $resentOtp,
                    'tipe_metod_mitra' => $user->tipe_metod_mitra,
                ],
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Login mitra failed.', ['exception' => $exception]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan pada server: '.$exception->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function verifyMitraOtp(array $payload): array
    {
        $user = $this->userMitraRepository->findByUserId((string) $payload['user_id']);

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User mitra tidak ditemukan.',
                'status' => 404,
            ];
        }

        $otpRecord = $this->otpVerificationRepository->findValidOtp(
            (string) $payload['user_id'],
            (string) $payload['otp']
        );

        if (! $otpRecord) {
            return [
                'success' => false,
                'message' => 'OTP tidak valid atau sudah kadaluarsa.',
                'status' => 422,
                'errors' => [
                    'otp' => ['OTP tidak valid atau sudah kadaluarsa.'],
                ],
            ];
        }

        $token = JWTAuth::fromUser($user);
        $this->otpVerificationRepository->deleteById($otpRecord->getKey());

        return [
            'success' => true,
            'message' => 'Verifikasi OTP mitra berhasil.',
            'status' => 200,
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => (int) config('jwt.ttl', 60) * 60,
                'user' => [
                    'id' => $user->id,
                    'user_id' => $user->user_id,
                    'mitra_id' => $user->mitra_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ];
    }
}
