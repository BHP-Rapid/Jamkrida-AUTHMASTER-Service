<?php

namespace App\Repositories;

use App\Models\NotifMitra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotifMitraRepository
{
    public function findById(int|string $id): ?NotifMitra
    {
        return NotifMitra::query()->find($id);
    }

    public function resolveNotificationTarget(string|int $id, ?string $role): string|int|null
    {
        if (in_array($role, ['mitra', 'head_admin_mitra'], true)) {
            return DB::table('user_mitra')
                ->where('id', $id)
                ->value('user_id');
        }

        if (in_array($role, ['admin_mitra', 'admin'], true)) {
            return DB::table('users')
                ->where('id', $id)
                ->value('mitra_id');
        }

        return $id;
    }

    public function paginateByTarget(string|int $targetId, int $perPage, int $page): LengthAwarePaginator
    {
        return NotifMitra::query()
            ->select('id', 'mitra_user_id', 'title', 'message', 'is_read', 'url', 'created_at')
            ->where('mitra_user_id', $targetId)
            ->orderBy('is_read', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function countUnreadByTarget(string|int $targetId): int
    {
        return NotifMitra::query()
            ->where('mitra_user_id', $targetId)
            ->where('is_read', '0')
            ->count();
    }

    public function markAsRead(NotifMitra $notif): bool
    {
        return $notif->update([
            'is_read' => true,
        ]);
    }

    public function markAllAsReadByTarget(string|int $targetId): int
    {
        return NotifMitra::query()
            ->where('mitra_user_id', $targetId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
            ]);
    }
}
