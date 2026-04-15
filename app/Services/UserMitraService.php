<?php

namespace App\Services;

use App\Mail\RegisterSuccessMail;
use App\Mail\RegisterSuccessMitraMail;
use App\Mail\UserVerificationMail;
use App\Models\TenantMitra;
use App\Models\UserMitra;
use App\Repositories\MasterRoleRepository;
use App\Repositories\UrlVerificationRepository;
use App\Repositories\UserMitraRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserMitraService
{
    public function __construct(
        protected UserMitraRepository $userMitraRepository,
        protected UrlVerificationRepository $urlVerificationRepository,
        protected MasterRoleRepository $masterRoleRepository,
    ) {
    }

    public function index(): array
    {
        return [
            'success' => true,
            'message' => 'Users retrieved successfully',
            'status' => 200,
            'data' => $this->userMitraRepository->findActiveUsers(),
        ];
    }

    public function getUsersByRole(array $payload): array
    {
        $results = $this->userMitraRepository->paginateUsers($payload);

        Log::info('User mitra list retrieved successfully', [
            'actor_user_id' => request()->user()?->user_id,
            'actor_role' => request()->user()?->role,
            'total' => $results->total(),
            'current_page' => $results->currentPage(),
            'per_page' => $results->perPage(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'success' => true,
            'status' => 200,
            'data' => $results,
        ];
    }

    public function getDataVerification(object $actor): array
    {
        if (($actor->role ?? null) !== 'super_admin') {
            $tenant = ! empty($actor->mitra_id)
                ? $this->findTenantMitra((string) $actor->mitra_id)
                : null;

            if (! $tenant) {
                Log::warning('User mitra verification retrieval failed: mitra not found', [
                    'actor_user_id' => $actor->user_id ?? null,
                    'actor_role' => $actor->role ?? null,
                    'actor_mitra_id' => $actor->mitra_id ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return ['success' => false, 'message' => 'Mitra not found', 'status' => 404];
            }
        }

        $results = $this->userMitraRepository->findVerificationUsersForActor($actor);

        Log::info('User mitra verification list retrieved successfully', [
            'actor_user_id' => $actor->user_id ?? null,
            'actor_role' => $actor->role ?? null,
            'total' => $results->count(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'success' => true,
            'status' => 200,
            'data' => $results,
        ];
    }

    public function store(object $actor, array $payload): array
    {
        if (! in_array($actor->role ?? null, ['admin_mitra', 'super_admin'], true)) {
            return ['success' => false, 'message' => 'Invalid user to create mitra', 'status' => 400];
        }

        $tenant = $this->findTenantMitra((string) $payload['mitra_id']);

        if (! $tenant) {
            Log::warning('User mitra create failed: mitra not found', [
                'payload' => $payload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'Mitra not found', 'status' => 404];
        }

        DB::beginTransaction();

        try {
            $insertPayload = [
                'user_id' => $this->generateUserId(
                    (string) $tenant->mitra_id,
                    (string) ($tenant->alias ?: $tenant->mitra_id),
                ),
                'mitra_id' => $tenant->mitra_id,
                'role' => $payload['role'],
                'email' => $payload['email'],
                'name' => $payload['name'],
                'phone' => $payload['phone'] ?? null,
                'status' => 'inactive',
                'statusApproval' => 'submitted',
                'created_at' => now(),
            ];

            $user = $this->userMitraRepository->create($insertPayload);

            DB::commit();

            Log::info('User mitra created successfully', [
                'target_user_id' => $user->user_id,
                'target_email' => $user->email,
                'target_role' => $user->role,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'success' => true,
                'message' => 'User mitra berhasil disimpan dan email verifikasi akun sudah dikirim',
                'status' => 200,
                'data' => $user,
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('User mitra create failed', [
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'Gagal menyimpan data: '.$exception->getMessage(), 'status' => 500];
        }
    }

    public function storeRegister(array $payload): array
    {
        $tenant = $this->findTenantMitra((string) $payload['mitra_id']);

        if (! $tenant) {
            Log::warning('User mitra register failed: mitra not found', [
                'payload' => $payload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'Mitra not found', 'status' => 404];
        }

        DB::beginTransaction();

        try {
            $insertPayload = [
                'user_id' => $this->generateUserId(
                    (string) $tenant->mitra_id,
                    (string) ($tenant->alias ?: $tenant->mitra_id),
                ),
                'mitra_id' => $tenant->mitra_id,
                'role' => $payload['role'],
                'email' => $payload['email'],
                'name' => $payload['name'],
                'phone' => $payload['phone'] ?? null,
                'status' => 'Active',
                'statusApproval' => 'Submitted',
                'created_at' => now(),
            ];

            $user = $this->userMitraRepository->create($insertPayload);

            DB::commit();

            Log::info('User mitra register successful', [
                'target_user_id' => $user->user_id,
                'target_email' => $user->email,
                'target_role' => $user->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => true, 'message' => 'Register berhasil.', 'status' => 200, 'data' => $user];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('User mitra register failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'Gagal menyimpan data: '.$exception->getMessage(), 'status' => 500];
        }
    }

    public function uploadExcel(array $rows): array
    {
        if (! is_array($rows)) {
            return ['success' => false, 'message' => 'Invalid input format. Expected an array.', 'status' => 400];
        }

        $inserted = [];
        $errors = [];

        $emailCounts = [];

        foreach ($rows as $row) {
            $rawEmail = $row['email'] ?? null;
            if (is_string($rawEmail)) {
                $normalized = strtolower(trim($rawEmail));
                if ($normalized !== '') {
                    $emailCounts[$normalized] = ($emailCounts[$normalized] ?? 0) + 1;
                }
            }
        }

        $batchDupSet = array_flip(array_keys(array_filter($emailCounts, fn (int $count): bool => $count > 1)));

        foreach ($rows as $index => $row) {
            try {
                $normalized = isset($row['email']) && is_string($row['email'])
                    ? strtolower(trim($row['email']))
                    : '';

                if ($normalized !== '' && isset($batchDupSet[$normalized])) {
                    $errors[] = [
                        'index' => $index,
                        'errors' => ['email' => ['Email duplikat pada batch input.']],
                    ];
                    continue;
                }

                $validated = Validator::make($row, [
                    'mitra_id' => ['required', 'string'],
                    'role' => ['required', 'string', 'in:pusat,cabang,head_admin_mitra,mitra'],
                    'email' => ['required', 'email:rfc,dns'],
                    'name' => ['required', 'string'],
                    'phone' => ['nullable', 'string'],
                    'status' => ['required', 'string'],
                    'statusApproval' => ['required', 'string'],
                ])->validate();

                $tenant = $this->findTenantMitra((string) $validated['mitra_id']);

                if (! $tenant) {
                    $errors[] = [
                        'index' => $index,
                        'errors' => ['mitra_id' => ['Mitra not found']],
                    ];
                    continue;
                }

                $latestUserId = $this->userMitraRepository->findLatestUserIdByMitraId((string) $tenant->mitra_id);

                if ($latestUserId && preg_match('/(\d+)$/', $latestUserId, $matches)) {
                    $lastNumber = (int) $matches[1];
                    $numberLength = strlen($matches[1]);
                    $nextNumber = str_pad((string) ($lastNumber + 1), $numberLength, '0', STR_PAD_LEFT);
                    $validated['user_id'] = preg_replace('/\d+$/', $nextNumber, $latestUserId);
                } else {
                    $validated['user_id'] = $validated['mitra_id'].'001';
                }

                $inserted[] = $this->userMitraRepository->create($validated);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                Log::error('User mitra upload row validation failed', [
                    'index' => $index,
                    'errors' => $exception->errors(),
                ]);

                $errors[] = [
                    'index' => $index,
                    'errors' => $exception->errors(),
                ];
            } catch (\Throwable $throwable) {
                Log::error('User mitra upload row failed', [
                    'index' => $index,
                    'error' => $throwable->getMessage(),
                ]);

                $errors[] = [
                    'index' => $index,
                    'errors' => ['exception' => [$throwable->getMessage()]],
                ];
            }
        }

        return [
            'success' => count($errors) === 0,
            'status' => 200,
            'data' => [
                'inserted_count' => count($inserted),
                'failed' => $errors,
                'data' => $inserted,
            ],
            'message' => 'Upload excel data user mitra berhasil disimpan',
        ];
    }

    public function getDataById(string $userId): array
    {
        $user = $this->userMitraRepository->findDetailedByUserId($userId);

        if (! $user) {
            return ['success' => false, 'message' => 'User mitra tidak ditemukan', 'status' => 404];
        }

        return ['success' => true, 'message' => 'User mitra berhasil diambil', 'status' => 200, 'data' => $user];
    }

    public function updateByUserId(object $actor, string $userId, array $payload): array
    {
        DB::beginTransaction();

        try {
            $user = $this->userMitraRepository->lockByUserId($userId);

            if (! $user) {
                DB::rollBack();

                return ['success' => false, 'message' => 'User mitra not found', 'status' => 404];
            }

            $validRoles = $this->masterRoleRepository->findCodesByType('mitra');

            if (! in_array($payload['role'], $validRoles, true)) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Invalid user mitra role', 'status' => 400];
            }

            $user->fill($payload);
            $before = $user->getOriginal();
            $user->save();

            DB::commit();

            Log::info('User mitra updated successfully', [
                'target_user_id' => $user->user_id,
                'target_email' => $user->email,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'before' => $before,
                'after' => $payload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => true, 'message' => 'User mitra updated successfully', 'status' => 200, 'data' => $user->fresh()];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('User mitra update failed', [
                'target_user_id' => $userId,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'Terjadi kesalahan: '.$exception->getMessage(), 'status' => 500];
        }
    }

    public function updateStatus(object $actor, string $userId, string $status): array
    {
        $normalizedStatus = ucfirst(strtolower($status));

        if (! in_array($normalizedStatus, ['Active', 'Inactive'], true)) {
            return ['success' => false, 'message' => 'Invalid status', 'status' => 422];
        }

        $user = $this->userMitraRepository->findByUserId($userId);

        if (! $user) {
            return ['success' => false, 'message' => 'User mitra not found', 'status' => 404];
        }

        $user->status = $status;
        $user->save();

        Log::info('User mitra status updated successfully', [
            'target_user_id' => $user->user_id,
            'target_email' => $user->email,
            'actor_user_id' => $actor->user_id ?? null,
            'actor_role' => $actor->role ?? null,
            'status' => $status,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return ['success' => true, 'message' => 'Status user mitra updated successfully', 'status' => 200, 'data' => $user];
    }

    public function updateStatusApproval(object $actor, string $userId, string $statusApproval): array
    {
        DB::beginTransaction();

        try {
            $user = $this->userMitraRepository->findByUserId($userId);

            if (! $user) {
                DB::rollBack();
                return ['success' => false, 'message' => 'User mitra not found', 'status' => 404];
            }

            $user->statusApproval = $statusApproval;
            $user->save();

            if (empty($user->password)) {
                $urlKey = (string) Str::uuid();

                $this->urlVerificationRepository->create([
                    'user_id' => $user->user_id,
                    'url_key' => $urlKey,
                    'valid_before' => now()->addHours(24),
                    'created_at' => now(),
                ]);

                Mail::to($user->email)->send(new UserVerificationMail(
                    $urlKey,
                    $user->name,
                    'mitra',
                    (string) $user->role,
                ));

                Log::info('User mitra verification email sent after status approval update', [
                    'target_user_id' => $user->user_id,
                    'target_email' => $user->email,
                    'target_role' => $user->role,
                    'status_approval' => $statusApproval,
                    'actor_user_id' => $actor->user_id ?? null,
                    'actor_role' => $actor->role ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            } elseif (($user->role ?? null) === 'admin') {
                Mail::to($user->email)->send(new RegisterSuccessMail($user->name, (string) $user->user_id));
            } elseif (in_array($user->role ?? null, ['pusat', 'cabang'], true)) {
                Mail::to($user->email)->send(new RegisterSuccessMitraMail($user->name, (string) $user->user_id));
            } else {
                Log::warning('Unrecognized user mitra role for success mail', [
                    'target_user_id' => $user->user_id,
                    'target_role' => $user->role,
                ]);
            }

            DB::commit();

            return ['success' => true, 'message' => 'Status user mitra updated successfully', 'status' => 200, 'data' => $user];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('User mitra status approval update failed', [
                'target_user_id' => $userId,
                'status_approval' => $statusApproval,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'Gagal update status: '.$exception->getMessage(), 'status' => 500];
        }
    }

    public function changePassword(object $user, array $payload): array
    {
        if (! $user instanceof UserMitra) {
            return ['success' => false, 'message' => 'Authenticated user mitra not found', 'status' => 404];
        }

        if (empty($user->password) || ! Hash::check($payload['current_password'], (string) $user->password)) {
            return ['success' => false, 'message' => 'Current password is incorrect', 'status' => 422];
        }

        $user->password = Hash::make($payload['new_password']);
        $user->save();

        Log::info('User mitra changed password', [
            'user_id' => $user->user_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return ['success' => true, 'message' => 'Password changed successfully', 'status' => 200];
    }

    public function deleteUser(object $actor, string $userId): array
    {
        try {
            $user = $this->userMitraRepository->findByUserId($userId);

            if (! $user) {
                return ['success' => false, 'message' => 'User tidak ditemukan.', 'status' => 404];
            }

            $this->userMitraRepository->updateByUserId($userId, [
                'deleted_at' => now(),
                'is_delete' => true,
                'deleted_by' => $actor->user_id ?? null,
                'status' => 'inactive',
                'statusApproval' => 'rejected',
            ]);

            return [
                'success' => true,
                'message' => 'User berhasil dihapus.',
                'status' => 200,
                'data' => $this->userMitraRepository->findByUserId($userId),
            ];
        } catch (\Throwable $exception) {
            Log::error('User mitra delete failed', [
                'target_user_id' => $userId,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'Terjadi kesalahan: '.$exception->getMessage(), 'status' => 500];
        }
    }

    protected function findTenantMitra(string $mitraId): ?TenantMitra
    {
        return TenantMitra::query()
            ->where('mitra_id', $mitraId)
            ->first();
    }

    protected function generateUserId(string $mitraId, string $alias): string
    {
        $year = now()->format('Y');
        $userCount = $this->userMitraRepository->countByMitraId($mitraId);
        $prefix = $alias;

        do {
            $userCount++;
            $sequence = str_pad((string) $userCount, 3, '0', STR_PAD_LEFT);
            $userId = $prefix.$year.$sequence;
        } while ($this->userMitraRepository->existsByUserId($userId));

        return $userId;
    }
}
