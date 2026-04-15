<?php

namespace App\Services;

use App\Repositories\NotifMitraRepository;
use Illuminate\Support\Facades\Log;

class NotifService
{
    public function __construct(
        protected NotifMitraRepository $notifMitraRepository,
        protected AuditTransactionService $auditTransactionService,
    ) {
    }

    public function getNotif(array $payload): array
    {
        try {
            $targetId = $this->notifMitraRepository->resolveNotificationTarget(
                $payload['id'] ?? '',
                $payload['role'] ?? null,
            );

            $perPage = (int) ($payload['limit'] ?? 5);
            $page = (int) ($payload['page'] ?? 1);
            $data = $this->notifMitraRepository->paginateByTarget($targetId, $perPage, $page);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'berhasil',
                'data' => $data->items(),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'last_page' => $data->lastPage(),
                ],
            ];

            $this->writeAudit('notif_mitra,user_mitra,users', $payload, true);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Get notif failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $this->writeAudit('notif_mitra,user_mitra,users', $payload, false);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'gagal',
                'data' => $exception->getMessage(),
            ];
        }
    }

    public function countNotif(array $payload): array
    {
        try {
            $targetId = $this->notifMitraRepository->resolveNotificationTarget(
                $payload['id'] ?? '',
                $payload['role'] ?? null,
            );

            $count = $this->notifMitraRepository->countUnreadByTarget($targetId);

            $result = [
                'success' => true,
                'status' => 200,
                'message' => 'data berhasil di-count',
                'data' => [
                    'count' => $count,
                ],
            ];

            $this->writeAudit('notif_mitra,user_mitra,users', $payload, true);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Count notif failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $this->writeAudit('notif_mitra,user_mitra,users', $payload, false);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'gagal',
                'data' => $exception->getMessage(),
            ];
        }
    }

    public function update(array $payload): array
    {
        try {
            $notif = $this->notifMitraRepository->findById((int) $payload['dataId']);

            if ($notif !== null) {
                $this->notifMitraRepository->markAsRead($notif);
            }

            $result = [
                'success' => true,
                'status' => 200,
                'message' => 'berhasil diupdate',
            ];

            $this->writeAudit('notif_mitra', $payload, true);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Update notif failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $this->writeAudit('notif_mitra', $payload, false);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'gagal',
                'data' => $exception->getMessage(),
            ];
        }
    }

    public function updateAllNotif(array $payload): array
    {
        try {
            $updatedCount = $this->notifMitraRepository->markAllAsReadByTarget($payload['user_id']);

            $result = [
                'success' => true,
                'status' => 200,
                'message' => $updatedCount > 0
                    ? 'Berhasil mengupdate '.$updatedCount.' notifikasi'
                    : 'Tidak ada notifikasi yang perlu diupdate untuk user ini',
                'data' => [
                    'updated_count' => $updatedCount,
                ],
            ];

            $this->writeAudit('notif_mitra', $payload, true);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Update all notif failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $this->writeAudit('notif_mitra', $payload, false);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Gagal mengupdate notifikasi',
                'data' => $exception->getMessage(),
            ];
        }
    }

    protected function writeAudit(string $targetTable, array $payload, bool $isSuccess): void
    {
        $user = request()->user();

        if (! $user) {
            return;
        }

        $this->auditTransactionService->logAuditTrail(
            method: request()->method(),
            endpoint: request()->fullUrl(),
            targetTable: $targetTable,
            userEmail: (string) ($user->email ?? ''),
            userRole: (string) ($user->role ?? ''),
            requestPayload: json_encode(['body' => $payload]),
            id: $user->user_id ?? $user->id ?? null,
            name: $user->name ?? null,
            isSuccess: $isSuccess,
        );
    }
}
