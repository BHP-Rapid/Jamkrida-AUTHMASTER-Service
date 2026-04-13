<?php

namespace App\Services;

use App\Mail\RegisterSuccessMail;
use App\Mail\UserVerificationMail;
use App\Repositories\UrlVerificationRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserAdminService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UrlVerificationRepository $urlVerificationRepository,
    ) {
    }

    public function index(): array
    {
        return [
            'success' => true,
            'message' => 'Users retrieved successfully',
            'status' => 200,
            'data' => $this->userRepository->findActiveUsers(),
        ];
    }

    public function getUsersByRole(object $actor, array $payload): array
    {
        if (! in_array($actor->role ?? null, ['admin', 'super_admin'], true)) {
            Log::warning('Unauthorized user list access attempt', [
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'No data', 'status' => 404];
        }

        $results = $this->userRepository->getUsersByRoleForAdmin($actor, $payload);

        Log::info('User list retrieved successfully', [
            'actor_user_id' => $actor->user_id ?? null,
            'actor_role' => $actor->role ?? null,
            'total' => $results->total(),
            'current_page' => $results->currentPage(),
            'per_page' => $results->perPage(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'success' => true,
            'message' => 'Users retrieved successfully',
            'status' => 200,
            'data' => $results,
        ];
    }

    public function getDataVerification(object $actor, array $payload): array
    {
        if (! in_array($actor->role ?? null, ['admin', 'super_admin'], true)) {
            Log::warning('Unauthorized verification list access attempt', [
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'No data', 'status' => 404];
        }

        $results = $this->userRepository->getVerificationUsers($actor, $payload);

        Log::info('Verification list retrieved successfully', [
            'actor_user_id' => $actor->user_id ?? null,
            'actor_role' => $actor->role ?? null,
            'total' => $results->total(),
            'current_page' => $results->currentPage(),
            'per_page' => $results->perPage(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'success' => true,
            'message' => 'Data verification retrieved successfully',
            'status' => 200,
            'data' => [
                'data' => $results->items(),
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ];
    }

    public function storeRegister(array $payload): array
    {
        DB::beginTransaction();

        try {
            if (in_array($payload['role'], ['admin', 'super_admin'], true)) {
                $payload['mitra_id'] = 'JMKRD';
            } elseif (empty($payload['mitra_id'])) {
                Log::warning('Invalid argument during registration', [
                    'error' => 'Mitra ID wajib diisi untuk role selain admin.',
                    'request_data' => collect($payload)->except(['password', 'password_confirmation'])->all(),
                ]);

                return ['success' => false, 'message' => 'Invalid input: Mitra ID wajib diisi untuk role selain admin.', 'status' => 400];
            }

            $payload['user_id'] = $this->generateUserId($this->resolveMitraAlias((string) $payload['mitra_id']), (string) $payload['mitra_id']);
            $payload['status'] = 'active';
            $payload['status_approval'] = 'submitted';
            $payload['password'] = '__PENDING_PASSWORD__';
            $payload['created_at'] = now();

            $user = $this->userRepository->create($payload);

            Log::info('User registration successful', [
                'user_id' => $user->user_id,
                'email' => $user->email,
                'role' => $user->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            return ['success' => true, 'message' => 'User registered successfully', 'status' => 200, 'data' => $user];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Error during user registration', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'request_data' => collect($payload)->except(['password', 'password_confirmation'])->all(),
            ]);

            return ['success' => false, 'message' => 'Registration failed: '.$exception->getMessage(), 'status' => 500];
        }
    }

    public function getDataById(string $userId): array
    {
        $user = $this->userRepository->findByUserId($userId);

        if (! $user) {
            return ['success' => false, 'message' => 'User admin tidak ditemukan', 'status' => 404];
        }

        return ['success' => true, 'message' => 'User admin berhasil diambil', 'status' => 200, 'data' => $user];
    }

    public function updateAdminByUserId(object $actor, string $userId, array $payload): array
    {
        DB::beginTransaction();

        try {
            $user = $this->userRepository->lockByUserId($userId);

            if (! $user) {
                DB::rollBack();

                Log::warning('User admin update failed: user not found', [
                    'target_user_id' => $userId,
                    'actor_user_id' => $actor->user_id ?? null,
                    'actor_role' => $actor->role ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return ['success' => false, 'message' => 'User admin not found', 'status' => 404];
            }

            if ($user->email !== $payload['email'] && $this->userRepository->findByEmail($payload['email'])) {
                DB::rollBack();

                Log::warning('User admin update failed: email already exists', [
                    'target_user_id' => $userId,
                    'target_email' => $payload['email'],
                    'actor_user_id' => $actor->user_id ?? null,
                    'actor_role' => $actor->role ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return ['success' => false, 'message' => 'Validation error: email already exists', 'status' => 422];
            }

            if (! empty($payload['password'])) {
                $payload['password'] = Hash::make($payload['password']);
            } else {
                unset($payload['password']);
            }

            $user->fill($payload);

            if (! $user->isDirty()) {
                DB::commit();

                Log::info('User admin update skipped: no changes detected', [
                    'target_user_id' => $user->user_id,
                    'target_email' => $user->email,
                    'actor_user_id' => $actor->user_id ?? null,
                    'actor_role' => $actor->role ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return ['success' => true, 'message' => 'No changes detected', 'status' => 200, 'data' => $user];
            }

            $before = $user->getOriginal();

            $user->save();
            DB::commit();

            Log::info('User admin updated successfully', [
                'target_user_id' => $user->user_id,
                'target_email' => $user->email,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'before' => $before,
                'after' => $payload,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => true, 'message' => 'User admin updated successfully', 'status' => 200, 'data' => $user->fresh()];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('User admin update failed', [
                'target_user_id' => $userId,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'payload' => collect($payload)->except(['password', 'password_confirmation'])->all(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'An error occurred while updating user admin: '.$exception->getMessage(), 'status' => 500];
        }
    }

    public function updateStatusApproval(object $actor, string $userId, string $statusApproval): array
    {
        DB::beginTransaction();

        try {
            $user = $this->userRepository->findByUserId($userId);

            if (! $user) {
                DB::rollBack();

                Log::warning('User admin status approval update failed: user not found', [
                    'target_user_id' => $userId,
                    'status_approval' => $statusApproval,
                    'actor_user_id' => $actor->user_id ?? null,
                    'actor_role' => $actor->role ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return ['success' => false, 'message' => 'User admin not found', 'status' => 404];
            }

            $user->status_approval = $statusApproval;
            $user->save();

            if (empty($user->password) || $user->password === '__PENDING_PASSWORD__') {
                $urlKey = (string) Str::uuid();
                $this->urlVerificationRepository->create([
                    'user_id' => $user->user_id,
                    'url_key' => $urlKey,
                    'valid_before' => now()->addHours(24),
                    'created_at' => now(),
                ]);

                Mail::to($user->email)->send(new UserVerificationMail($urlKey, $user->name, 'admin', (string) $user->role));

                Log::info('User verification email sent after status approval update', [
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

                Log::info('Register success email sent after status approval update', [
                    'target_user_id' => $user->user_id,
                    'target_email' => $user->email,
                    'target_role' => $user->role,
                    'status_approval' => $statusApproval,
                    'actor_user_id' => $actor->user_id ?? null,
                    'actor_role' => $actor->role ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            DB::commit();

            Log::info('User admin status approval updated successfully', [
                'target_user_id' => $user->user_id,
                'target_email' => $user->email,
                'target_role' => $user->role,
                'status_approval' => $statusApproval,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => true, 'message' => 'Status user admin updated successfully', 'status' => 200, 'data' => $user];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('User admin status approval update failed', [
                'target_user_id' => $userId,
                'status_approval' => $statusApproval,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'An error occurred while updating status: '.$exception->getMessage(), 'status' => 500];
        }
    }

    public function updateStatus(object $actor, string $userId, string $status): array
    {
        $user = $this->userRepository->findByUserId($userId);

        if (! $user) {
            Log::warning('User admin status update failed: user not found', [
                'target_user_id' => $userId,
                'status' => $status,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'User mitra not found', 'status' => 404];
        }

        $user->status = $status;
        $user->save();

        Log::info('User admin status updated successfully', [
            'target_user_id' => $user->user_id,
            'target_email' => $user->email,
            'target_role' => $user->role,
            'status' => $status,
            'actor_user_id' => $actor->user_id ?? null,
            'actor_role' => $actor->role ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return ['success' => true, 'message' => 'Status user mitra updated successfully', 'status' => 200, 'data' => $user];
    }

    public function changePassword(object $user, array $payload): array
    {
        if (! Hash::check($payload['current_password'], (string) $user->password)) {
            Log::warning('User admin change password failed: current password mismatch', [
                'actor_user_id' => $user->user_id ?? null,
                'actor_role' => $user->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'Current password is incorrect', 'status' => 422];
        }

        $user->password = Hash::make($payload['new_password']);
        $user->save();

        Log::info('User admin password changed successfully', [
            'actor_user_id' => $user->user_id ?? null,
            'actor_role' => $user->role ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return ['success' => true, 'message' => 'Password changed successfully', 'status' => 200];
    }

    public function deleteUser(object $actor, string $userId): array
    {
        $user = $this->userRepository->findByUserId($userId);

        if (! $user) {
            Log::warning('User admin delete failed: user not found', [
                'target_user_id' => $userId,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'User tidak ditemukan.', 'status' => 404];
        }

        try {
            $this->userRepository->updateByUserId($userId, [
                'deleted_at' => now(),
                'is_delete' => true,
                'deleted_by' => $actor->user_id ?? null,
                'status' => 'inactive',
                'status_approval' => 'rejected',
            ]);

            $deletedUser = $this->userRepository->findByUserId($userId);

            Log::info('User admin deleted successfully', [
                'target_user_id' => $userId,
                'target_email' => $user->email,
                'target_role' => $user->role,
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => true, 'message' => 'User berhasil dihapus.', 'status' => 200, 'data' => $deletedUser];
        } catch (\Throwable $exception) {
            Log::error('User admin delete failed', [
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

    public function getRoleList(object $actor): array
    {
        if (! in_array($actor->role ?? null, ['admin', 'super_admin'], true)) {
            Log::warning('Role list access denied', [
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'Invalid user to get role list', 'status' => 400];
        }

        $roleList = [];

        if (($actor->role ?? null) === 'super_admin') {
            $roleList = [
                ['value' => 'admin', 'label' => 'Admin Jamkrida'],
                ['value' => 'super_admin', 'label' => 'Super Admin'],
                ['value' => 'admin_mitra', 'label' => 'Admin Mitra'],
            ];
        } elseif (($actor->role ?? null) === 'admin') {
            $roleList[] = ['value' => 'admin_mitra', 'label' => 'Admin Mitra'];
        }

        Log::info('Role list retrieved successfully', [
            'actor_user_id' => $actor->user_id ?? null,
            'actor_role' => $actor->role ?? null,
            'total' => count($roleList),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return ['success' => true, 'message' => 'Return data succesful.', 'status' => 200, 'data' => $roleList];
    }

    public function getAdminMitraList(object $actor): array
    {
        if (($actor->role ?? null) === 'admin_mitra') {
            Log::warning('Admin mitra list access denied', [
                'actor_user_id' => $actor->user_id ?? null,
                'actor_role' => $actor->role ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['success' => false, 'message' => 'No access to this data', 'status' => 403];
        }

        $results = $this->userRepository->getAdminMitraList();

        Log::info('Admin mitra list retrieved successfully', [
            'actor_user_id' => $actor->user_id ?? null,
            'actor_role' => $actor->role ?? null,
            'total' => $results->count(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return ['success' => true, 'message' => 'Return data succesful.', 'status' => 200, 'data' => $results];
    }

    protected function resolveMitraAlias(string $mitraId): string
    {
        try {
            $row = DB::table('tenant_mitra')->where('mitra_id', $mitraId)->select('alias')->first();
            return $row?->alias ?: $mitraId;
        } catch (\Throwable) {
            return $mitraId;
        }
    }

    protected function generateUserId(string $mitraAlias, string $mitraId): string
    {
        $sliceYear = substr(now()->format('Y'), 2);
        $mitraIdUpper = strtoupper($mitraAlias);
        $userCount = $this->userRepository->countByMitraId($mitraId);

        do {
            $userCount++;
            $formattedCount = str_pad((string) $userCount, 3, '0', STR_PAD_LEFT);
            $userId = $mitraIdUpper.$sliceYear.$formattedCount;
        } while ($this->userRepository->existsByUserId($userId));

        return $userId;
    }
}
