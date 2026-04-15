<?php

namespace App\Services;

use App\Repositories\MitraRepository;
use App\Repositories\TenantMitraRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MitraService
{
    public function __construct(
        protected CreatioService $creatioService,
        protected MitraRepository $mitraRepository,
        protected TenantMitraRepository $tenantMitraRepository,
    ) {
    }

    public function getDataByMitraId(array $payload): array
    {
        try {
            $response = $this->creatioService->request('get', '/0/rest/MitraWebService/GetData');

            if ($response->status() !== 200) {
                throw new \Exception('Failed to get Mitra from Core Creatio API with status: '.$response->status());
            }

            $apiResBody = json_decode($response->body(), true);

            if (($apiResBody['Success'] ?? false) !== true) {
                throw new \Exception('Failed to get data from Core Creatio API with message: '.($apiResBody['Message'] ?? 'No message'));
            }

            $value = (string) $payload['mitra_id'];
            $items = collect($apiResBody['Data'] ?? [])->filter(function ($item) use ($value) {
                $mitraId = data_get($item, 'id', '');

                return stripos((string) $mitraId, $value) !== false;
            })->values();

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data Mitra berhasil diambil.',
                'data' => $items,
            ];
        } catch (\Throwable $exception) {
            Log::error('Get data by mitra id failed', [
                'mitra_id' => $payload['mitra_id'] ?? null,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Gagal Mengambil Data Mitra: '.$exception->getMessage(),
            ];
        }
    }

    public function getMitraFromCreatio(array $payload): array
    {
        try {
            $response = $this->creatioService->request('get', '/0/rest/MitraWebService/GetData');

            if ($response->status() !== 200) {
                throw new \Exception('Failed to get data from Core Creatio API with status: '.$response->status());
            }

            $apiResBody = json_decode($response->body(), true);

            if (($apiResBody['Success'] ?? false) !== true) {
                throw new \Exception('Failed to get data from Core Creatio API with message: '.($apiResBody['Message'] ?? 'No message'));
            }

            $items = collect($apiResBody['Data'] ?? [])
                ->filter(function ($item) {
                    $mitraId = data_get($item, 'mitra_id');

                    return ! is_null($mitraId) && $mitraId !== '' && trim((string) $mitraId) !== '';
                });

            foreach (($payload['filter'] ?? []) as $filterItem) {
                $field = $filterItem['field'] ?? null;
                $value = $filterItem['value'] ?? null;

                if ($field === null || $value === null) {
                    continue;
                }

                $items = $this->applyCreatioFilter($items, (string) $field, (string) $value);
            }

            $sortColumn = $payload['sort_column'] ?? null;
            $sortDir = $payload['sort'] ?? 'asc';

            if ($sortColumn) {
                $items = $sortDir === 'desc'
                    ? $items->sortByDesc(fn ($it) => data_get($it, $sortColumn))
                    : $items->sortBy(fn ($it) => data_get($it, $sortColumn));
            }

            $items = $items->values();
            $page = max(1, (int) ($payload['page'] ?? 1));
            $perPage = max(1, (int) ($payload['show_page'] ?? 10));
            $total = $items->count();

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data Mitra dari Creatio berhasil diambil.',
                'data' => [
                    'data' => $items->forPage($page, $perPage)->values()->all(),
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                ],
            ];
        } catch (\Throwable $exception) {
            Log::error('Get mitra from Creatio failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'success' => false,
                'status' => 500,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function getDataMitra(): array
    {
        try {
            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data mitra berhasil diambil.',
                'data' => $this->tenantMitraRepository->getActiveList(),
            ];
        } catch (\Throwable $exception) {
            Log::error('Get data mitra failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'success' => false,
                'status' => 500,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function store(array $payload): array
    {
        DB::beginTransaction();

        try {
            $response = $this->creatioService->request('post', '/0/rest/MitraWebService/Create', [
                'mitra_id' => $payload['mitra_id'],
                'name_mitra' => $payload['name_mitra'],
                'email' => $payload['email'],
                'phone_number' => $payload['phone_number'],
                'address' => $payload['address'],
                'status_active' => $payload['status'],
            ]);

            $this->mitraRepository->create([
                'mitra_id' => $payload['mitra_id'],
                'name_mitra' => $payload['name_mitra'],
                'email' => $payload['email'],
                'phone_number' => $payload['phone_number'],
                'address' => $payload['address'],
                'status' => 'Active',
            ]);

            if ($response->status() !== 200) {
                throw new \Exception('Failed to register Mitra to Core Creatio API with status: '.$response->status());
            }

            $bodyResponse = json_decode($response->body(), true);

            if (($bodyResponse['Success'] ?? false) !== true) {
                throw new \Exception('Failed to register Mitra to Core Creatio API with message: '.($bodyResponse['Message'] ?? 'No message'));
            }

            $notifRes = $this->creatioService->request('post', '/0/rest/Notification/SendNotification', [
                'Title' => 'Mitra Portal Notification',
                'Subject' => 'Register Mitra Success',
                'Contact' => 'Supervisor',
            ]);

            if ($notifRes->status() !== 200) {
                throw new \Exception('Failed to send notification to Core Creatio API with status: '.$notifRes->status());
            }

            $notifResBody = json_decode($notifRes->body(), true);

            if (($notifResBody['Success'] ?? false) !== true) {
                throw new \Exception('Failed to send notification to Core Creatio API with message: '.($notifResBody['Message'] ?? 'No message'));
            }

            DB::commit();

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data Mitra berhasil disimpan',
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Store mitra failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Gagal menyimpan data: '.$exception->getMessage(),
            ];
        }
    }

    public function updateByMitraId(array $payload): array
    {
        DB::beginTransaction();

        try {
            $response = $this->creatioService->request(
                'put',
                '/0/rest/MitraWebService/Update?recordId='.$payload['id'],
                [
                    'mitra_id' => $payload['mitra_id'] ?? null,
                    'name_mitra' => $payload['name_mitra'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'phone_number' => $payload['phone_number'] ?? null,
                    'address' => $payload['address'] ?? null,
                    'status_active' => $payload['status'] ?? null,
                ],
            );

            if ($response->status() !== 200) {
                throw new \Exception('Failed to get Mitra from Core Creatio API with status: '.$response->status());
            }

            $bodyResponse = json_decode($response->body(), true);

            if (($bodyResponse['Success'] ?? false) !== true) {
                throw new \Exception('Failed to register Mitra to Core Creatio API with message: '.($bodyResponse['Message'] ?? 'No message'));
            }

            $notifRes = $this->creatioService->request('post', '/0/rest/Notification/SendNotification', [
                'Title' => 'Mitra Portal Notification',
                'Subject' => 'Update Mitra '.($payload['name_mitra'] ?? '').' Success',
                'Contact' => 'Supervisor',
            ]);

            if ($notifRes->status() !== 200) {
                throw new \Exception('Failed to send notification to Core Creatio API with status: '.$notifRes->status());
            }

            $notifResBody = json_decode($notifRes->body(), true);

            if (($notifResBody['Success'] ?? false) !== true) {
                throw new \Exception('Failed to send notification to Core Creatio API with message: '.($notifResBody['Message'] ?? 'No message'));
            }

            DB::commit();

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data Mitra berhasil diupdate.',
                'data' => [$bodyResponse],
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Update mitra by mitra id failed', [
                'payload' => $payload,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Failed to fetch data: '.$exception->getMessage(),
            ];
        }
    }

    private function applyCreatioFilter(Collection $items, string $field, string $value): Collection
    {
        return match ($field) {
            'name_mitra' => $items->filter(function ($item) use ($value) {
                return stripos((string) data_get($item, 'name_mitra', ''), $value) !== false;
            }),
            'mitra_id' => $items->filter(function ($item) use ($value) {
                return stripos((string) data_get($item, 'mitra_id', ''), $value) !== false;
            }),
            default => $items->filter(function ($item) use ($field, $value) {
                return stripos((string) data_get($item, $field, ''), $value) !== false;
            }),
        };
    }
}
