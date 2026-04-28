<?php

namespace App\Services;

use App\Helpers\AesHelper;
use App\Mail\ResendEmailforResetPasswordMail;
use App\Mail\SendOtpToMitra;
use App\Models\User;
use App\Models\UserMitra;
use App\Repositories\OTPVerificationRepository;
use App\Repositories\RefreshTokenRepository;
use App\Repositories\SettingProductDetailRepository;
use App\Repositories\UrlVerificationRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserMitraRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\TenantMitra; 
class AuthService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UserMitraRepository $userMitraRepository,
        protected OTPVerificationRepository $otpVerificationRepository,
        protected RefreshTokenRepository $refreshTokenRepository,
        protected SettingProductDetailRepository $settingProductDetailRepository,
        protected UrlVerificationRepository $urlVerificationRepository,
    ) {
    }

    // public function login(array $credentials): array
    // {
    //     $user = $this->userRepository->findByEmail((string) ($credentials['email'] ?? ''));

    //     if (! $user) {
    //         return [
    //             'success' => false,
    //             'message' => 'User tidak ditemukan.',
    //             'status' => 404,
    //             'errors' => [
    //                 'email' => ['Email tidak terdaftar.'],
    //             ],
    //         ];
    //     }

    //     $hashedPassword = (string) $user->password;

    //     if ($hashedPassword === '' || ! Hash::check((string) ($credentials['password'] ?? ''), $hashedPassword)) {
    //         return [
    //             'success' => false,
    //             'message' => 'Password tidak valid.',
    //             'status' => 422,
    //             'errors' => [
    //                 'password' => ['Password yang diberikan tidak sesuai.'],
    //             ],
    //         ];
    //     }

    //     return [
    //         'success' => true,
    //         'message' => 'Login berhasil.',
    //         'status' => 200,
    //         'data' => [
    //             'user' => [
    //                 'id' => $user->id,
    //                 'name' => $user->name,
    //                 'email' => $user->email,
    //             ],
    //         ],
    //     ];
    // }

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
            'valid_before' => now()->addMinutes($otpDuration),
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

        $this->otpVerificationRepository->deleteById($otpRecord->getKey());

        return [
            'success' => true,
            'message' => 'Verifikasi OTP berhasil.',
            'status' => 200,
            'data' => $this->issueTokenPair($user),
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
                'valid_before' => now()->addMinutes($otpDuration),
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

        $this->otpVerificationRepository->deleteById($otpRecord->getKey());

        return [
            'success' => true,
            'message' => 'Verifikasi OTP mitra berhasil.',
            'status' => 200,
            'data' => $this->issueTokenPair($user),
        ];
    }

    public function refreshToken(array $payload = [], ?string $currentAccessToken = null): array
    {
        $refreshToken = (string) ($payload['refresh_token'] ?? '');

        if ($refreshToken !== '') {
            return $this->refreshUsingStoredToken($refreshToken);
        }

        try {
            $currentToken = ($currentAccessToken !== null && $currentAccessToken !== '')
                ? $currentAccessToken
                : JWTAuth::getToken();

            if (! $currentToken) {
                return [
                    'success' => false,
                    'message' => 'Unauthorized: token missing.',
                    'status' => 401,
                ];
            }

            $payload = JWTAuth::setToken($currentToken)->getPayload();
            $authType = (string) $payload->get('auth_type', 'admin');
            $subject = $payload->get('sub');

            $user = match ($authType) {
                'mitra' => $this->userMitraRepository->findById($subject),
                default => $this->userRepository->findById($subject),
            };

            if (! $user) {
                return [
                    'success' => false,
                    'message' => 'Unauthorized: user not found.',
                    'status' => 401,
                ];
            }

            $newToken = JWTAuth::setToken($currentToken)->refresh();
            $newRefreshToken = $this->createRefreshTokenForUser($user);

            return [
                'success' => true,
                'message' => 'Refresh token berhasil.',
                'status' => 200,
                'data' => $this->buildTokenPayload($user, $newToken, $newRefreshToken),
            ];
        } catch (TokenExpiredException) {
            return [
                'success' => false,
                'message' => 'Unauthorized: token expired.',
                'status' => 401,
            ];
        } catch (TokenInvalidException) {
            return [
                'success' => false,
                'message' => 'Unauthorized: token invalid.',
                'status' => 401,
            ];
        } catch (JWTException) {
            return [
                'success' => false,
                'message' => 'Unauthorized: token missing.',
                'status' => 401,
            ];
        }
    }

    public function validateResetUrl(string $urlKey, string $userType): array
    {
        $record = $this->urlVerificationRepository->findByUrlKey($urlKey);

        if (! $record || Carbon::parse($record->valid_before)->isPast()) {
            return [
                'success' => false,
                'message' => 'Link tidak valid atau telah kadaluarsa.',
                'status' => 400,
            ];
        }

        $user = match ($userType) {
            'admin' => $this->userRepository->findByUserId($record->user_id),
            'mitra' => $this->userMitraRepository->findByUserId((string) $record->user_id),
            default => null,
        };

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan.',
                'status' => 404,
            ];
        }

        return [
            'success' => true,
            'message' => 'Link valid, silakan lanjutkan reset password.',
            'status' => 200,
            'data' => [
                'user_id' => $user->user_id ?? $user->id,
                'email' => $user->email,
            ],
        ];
    }

    public function resetPassword(array $payload): array
    {
        $user = match ($payload['user_type']) {
            'admin' => $this->userRepository->findByUserId($payload['user_id']),
            'mitra' => $this->userMitraRepository->findByUserId((string) $payload['user_id']),
            default => null,
        };

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan.',
                'status' => 404,
            ];
        }

        $hashedPassword = Hash::make((string) $payload['password']);

        match ($payload['user_type']) {
            'admin' => $this->userRepository->updatePassword($user, $hashedPassword),
            'mitra' => $this->userMitraRepository->updatePassword($user, $hashedPassword),
        };

        return [
            'success' => true,
            'message' => 'Password berhasil diperbarui, Silahkan login kembali.',
            'status' => 200,
        ];
    }

    public function resendResetPasswordEmail(array $payload): array
    {
        $user = match ($payload['user_type']) {
            'admin' => isset($payload['email']) ? $this->userRepository->findByEmail((string) $payload['email']) : null,
            'mitra' => isset($payload['user_id']) ? $this->userMitraRepository->findByUserId((string) $payload['user_id']) : null,
            default => null,
        };

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan.',
                'status' => 404,
            ];
        }

        $urlKey = (string) Str::uuid();

        $this->urlVerificationRepository->create([
            'user_id' => $user->user_id ?? $user->id,
            'url_key' => $urlKey,
            'valid_before' => now()->addHours(24),
            'created_at' => now(),
        ]);

        Mail::to($user->email)->send(new ResendEmailforResetPasswordMail(
            $urlKey,
            $user->name,
            (string) ($user->role ?? ''),
            (string) $payload['user_type'],
        ));

        return [
            'success' => true,
            'message' => "Please check the email address {$user->email} for instructions to reset your password.",
            'status' => 200,
        ];
    }

    protected function issueTokenPair(object $user): array
    {
        $accessToken = JWTAuth::fromUser($user);
        $refreshToken = $this->createRefreshTokenForUser($user);

        return $this->buildTokenPayload($user, $accessToken, $refreshToken);
    }

    protected function buildTokenPayload(object $user, string $accessToken, ?string $refreshToken = null): array
    {
        $DataTenant = $this->resolveGetTenantNameAndMitrAliasForUser($user);
        return [
            'token' => $accessToken,
            'access_token' => $accessToken,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
            'access_token_expires_in' => (int) config('jwt.ttl', 60) * 60,
            'refresh_token' => $refreshToken,
            'refresh_token_expires_in' => $this->getRefreshTokenTtlInMinutes() * 60,
            'user' => [
                'id' => $user->id,
                'user_id' => $user->user_id ?? $user->id,
                'mitra_id' => $user->mitra_id ?? null,
                'mitra_name' => $DataTenant['mitra_alias'] ?? null,
                'tenant_id' => $DataTenant['tenant_id'] ?? null,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'tenant_name' => $DataTenant['tenant_name'] ?? null,
            ],
        ];
    }

    protected function createRefreshTokenForUser(object $user): string
    {
        $plainTextToken = bin2hex(random_bytes(48));

        $this->refreshTokenRepository->create([
            'token_hash' => $this->refreshTokenRepository->hashToken($plainTextToken),
            'auth_type' => $this->resolveAuthTypeForUser($user),
            'subject_id' => (string) $user->getKey(),
            'user_id' => (string) ($user->user_id ?? $user->getKey()),
            'expires_at' => now()->addMinutes($this->getRefreshTokenTtlInMinutes()),
        ]);

        return $plainTextToken;
    }

    protected function refreshUsingStoredToken(string $refreshToken): array
    {
        $storedToken = $this->refreshTokenRepository->findActiveByPlainTextToken($refreshToken);

        if (! $storedToken) {
            return [
                'success' => false,
                'message' => 'Unauthorized: refresh token invalid or expired.',
                'status' => 401,
            ];
        }

        $user = $this->resolveUserByAuthContext($storedToken->auth_type, $storedToken->subject_id);

        if (! $user) {
            $this->refreshTokenRepository->revoke($storedToken);

            return [
                'success' => false,
                'message' => 'Unauthorized: user not found.',
                'status' => 401,
            ];
        }

        return DB::transaction(function () use ($storedToken, $user): array {
            $accessToken = JWTAuth::fromUser($user);
            $newRefreshToken = bin2hex(random_bytes(48));

            $replacementToken = $this->refreshTokenRepository->create([
                'token_hash' => $this->refreshTokenRepository->hashToken($newRefreshToken),
                'auth_type' => $storedToken->auth_type,
                'subject_id' => (string) $storedToken->subject_id,
                'user_id' => (string) ($user->user_id ?? $user->getKey()),
                'expires_at' => now()->addMinutes($this->getRefreshTokenTtlInMinutes()),
            ]);

            $this->refreshTokenRepository->rotate($storedToken, $replacementToken);

            return [
                'success' => true,
                'message' => 'Refresh token berhasil.',
                'status' => 200,
                'data' => $this->buildTokenPayload($user, $accessToken, $newRefreshToken),
            ];
        });
    }

    protected function resolveAuthTypeForUser(object $user): string
    {
        return $user instanceof UserMitra ? 'mitra' : 'admin';
    }

    protected function resolveTenantIdForUser(object $user): ?string
    {
        $tenantId = $user->tenant_id ?? null;

        if ($tenantId !== null && $tenantId !== '') {
            return (string) $tenantId;
        }

        $mitraId = $user->mitra_id ?? null;

        if ($mitraId === null || $mitraId === '') {
            return null;
        }

        if (
            ! Schema::hasTable('tenant_mitra')
            || ! Schema::hasColumn('tenant_mitra', 'tenant_id')
            || ! Schema::hasColumn('tenant_mitra', 'mitra_id')
        ) {
            return null;
        }

        $tenantId = DB::table('tenant_mitra')
            ->where('mitra_id', $mitraId)
            ->value('tenant_id');

        return $tenantId !== null && $tenantId !== '' ? (string) $tenantId : null;
    }

    protected function resolveGetTenantNameAndMitrAliasForUser(object $user): array
    {
        $tenantId = $this->resolveTenantIdForUser($user);

        if ($tenantId === null) {
            return [
                'tenant_name' => null,
                'mitra_alias' => null,
            ];
        }

        $tenantRecord = DB::table('tenant_mitra')
            ->where('tenant_id', $tenantId)
            ->first(['name as tenant_name', 'alias as mitra_alias']);

        return [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantRecord->tenant_name ?? null,
            'mitra_alias' => $tenantRecord->mitra_alias ?? null,
        ];
    }

    protected function resolveUserByAuthContext(string $authType, int|string $subjectId): User|UserMitra|null
    {
        return match ($authType) {
            'mitra' => $this->userMitraRepository->findById($subjectId),
            default => $this->userRepository->findById($subjectId),
        };
    }

    protected function getRefreshTokenTtlInMinutes(): int
    {
        return (int) config('auth.refresh_token_ttl', 43200);
    }
}
