<?php

namespace App\Services;

use App\Jobs\SendBulkNotification;
use App\Repositories\NotifMitraAdminRepository;
use Illuminate\Support\Facades\Log;

class NotifAdminService
{
    public function __construct(
        protected NotifMitraAdminRepository $notifMitraAdminRepository,
        protected AuditTransactionService $auditTransactionService,
    ) {
    }

    public function index(array $payload): array
    {
        try {
            $results = $this->notifMitraAdminRepository->paginate($payload);

            $result = [
                'success' => true,
                'status' => 200,
                'message' => 'Data notifikasi admin berhasil diambil.',
                'data' => $results,
            ];

            $this->writeAudit('notif_mitra_admin', $payload, true);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Notif admin retrieval failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $this->writeAudit('notif_mitra_admin', $payload, false);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'gagal',
                'data' => $exception->getMessage(),
            ];
        }
    }

    public function getMitraRecipient(array $payload): array
    {
        try {
            $perPage = (int) ($payload['limit'] ?? 5);
            $page = (int) ($payload['page'] ?? 1);
            $data = $this->notifMitraAdminRepository->paginateMitraRecipients($perPage, $page);

            $result = [
                'success' => true,
                'status' => 200,
                'message' => 'Users retrieved successfully',
                'data' => $data->items(),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'last_page' => $data->lastPage(),
                ],
            ];

            $this->writeAudit('user_mitra', $payload, true);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Notif admin recipient retrieval failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $this->writeAudit('user_mitra', $payload, false);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'gagal',
                'data' => $exception->getMessage(),
            ];
        }
    }

    public function createNotifAdmin(array $payload): array
    {
        try {
            $recipientType = (string) $payload['recipientType'];
            $recipient = array_values($payload['recipient'] ?? []);

            $notif = $this->notifMitraAdminRepository->create([
                'title' => $payload['title'],
                'message' => $payload['message'],
                'recipient_type' => $recipientType,
                'recipient' => $recipientType === 'all' ? json_encode([]) : json_encode($recipient),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            SendBulkNotification::dispatch(
                (string) $payload['title'],
                (string) $payload['message'],
                $recipientType,
                $recipient,
            );

            $result = [
                'success' => true,
                'status' => 200,
                'message' => 'notifikasi berhasil disimpan',
                'data' => [
                    'id' => $notif->id,
                    'recipient_type' => $recipientType,
                    'recipient_count' => $recipientType === 'all' ? null : count($recipient),
                ],
            ];

            $this->writeAudit('notif_mitra_admin,notif_mitra', $payload, true);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Notif admin creation failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $this->writeAudit('notif_mitra_admin,notif_mitra', $payload, false);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'gagal',
                'data' => $exception->getMessage(),
            ];
        }
    }

    public function getById(int $id): array
    {
        try {
            $data = $this->notifMitraAdminRepository->findById($id);

            if (! $data) {
                $this->writeAudit('notif_mitra_admin,user_mitra', ['id' => $id], false);

                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'Notifikasi tidak ditemukan',
                ];
            }

            $recipientIds = json_decode((string) ($data->recipient ?? '[]'), true);
            $recipientIds = is_array($recipientIds) ? $recipientIds : [];

            $recipients = $this->notifMitraAdminRepository->findRecipientsByUserIds($recipientIds);

            if (count($recipientIds) !== $recipients->count()) {
                $this->writeAudit('notif_mitra_admin,user_mitra', ['id' => $id], false);

                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'User tidak ditemukan',
                ];
            }

            $result = [
                'success' => true,
                'status' => 200,
                'message' => 'Users retrieved successfully',
                'data' => [
                    'title' => $data->title,
                    'message' => $data->message,
                    'recipient_type' => $data->recipient_type,
                    'recipient' => $recipients->values()->all(),
                ],
            ];

            $this->writeAudit('notif_mitra_admin,user_mitra', ['id' => $id], true);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Notif admin detail retrieval failed', [
                'id' => $id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $this->writeAudit('notif_mitra_admin,user_mitra', ['id' => $id], false);

            return [
                'success' => false,
                'status' => 500,
                'message' => $exception->getMessage(),
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
