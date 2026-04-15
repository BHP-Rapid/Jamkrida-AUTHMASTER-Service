<?php

namespace App\Repositories;

use App\Models\NotifMitraAdmin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotifMitraAdminRepository
{
    public function create(array $payload): NotifMitraAdmin
    {
        return NotifMitraAdmin::query()->create($payload);
    }

    public function findById(int $id): ?NotifMitraAdmin
    {
        return NotifMitraAdmin::query()->find($id);
    }

    public function paginate(array $payload): LengthAwarePaginator
    {
        $query = NotifMitraAdmin::query()
            ->select('id', 'title', 'message', 'recipient_type', 'created_at');

        foreach (($payload['filter'] ?? []) as $filterItem) {
            $filterId = $filterItem['id'] ?? null;
            $filterValue = $filterItem['value'] ?? null;

            if ($filterId === null || $filterValue === null) {
                continue;
            }

            switch ($filterId) {
                case 'title':
                    $query->where('title', 'like', '%'.$filterValue.'%');
                    break;
                case 'message':
                    $query->where('message', 'like', '%'.$filterValue.'%');
                    break;
                case 'recipient_type':
                    $query->where('recipient_type', 'like', '%'.$filterValue.'%');
                    break;
                default:
                    break;
            }
        }

        $sortColumns = [
            'id' => 'id',
            'bulk_no' => 'bulk_no',
            'title' => 'title',
            'message' => 'message',
            'recipient_type' => 'recipient_type',
        ];

        $sortColumn = $sortColumns[$payload['sort_column'] ?? ''] ?? 'created_at';
        $sortOrder = $payload['sort'] ?? 'desc';
        $perPage = (int) ($payload['show_page'] ?? 10);

        return $query
            ->orderBy($sortColumn, $sortOrder)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends($payload);
    }

    public function paginateMitraRecipients(int $perPage, int $page): LengthAwarePaginator
    {
        return DB::table('user_mitra')
            ->select('user_id', 'name', 'status')
            ->where('role', 'mitra')
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findRecipientsByUserIds(array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return DB::table('user_mitra')
            ->select('user_id', 'name', 'status')
            ->whereIn('user_id', $userIds)
            ->get();
    }
}
