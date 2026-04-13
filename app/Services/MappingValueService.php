<?php

namespace App\Services;

use App\Repositories\MappingValueRepository;
use Creasi\Nusa\Models\District;
use Creasi\Nusa\Models\Province;
use Creasi\Nusa\Models\Regency;
use Creasi\Nusa\Models\Village;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MappingValueService
{
    public function __construct(
        protected MappingValueRepository $mappingValueRepository,
    ) {
    }

    public function index(): array
    {
        return [
            'success' => true,
            'message' => 'Data mapping value berhasil diambil.',
            'status' => 200,
            'data' => $this->mappingValueRepository->all(),
        ];
    }

    public function indexTableMapping(array $payload): array
    {
        $filters = [];

        foreach (($payload['filter'] ?? []) as $filterItem) {
            if (($filterItem['id'] ?? null) === 'key') {
                $filters['key'] = $filterItem['value'] ?? null;
            }
        }

        $sortColumns = [
            'key' => 'key',
        ];

        $sortColumn = $sortColumns[$payload['sort_column'] ?? ''] ?? 'updated_at';
        $sortOrder = $payload['sort'] ?? 'desc';
        $perPage = (int) ($payload['show_page'] ?? 10);

        return [
            'success' => true,
            'message' => 'Data table mapping berhasil diambil.',
            'status' => 200,
            'data' => $this->mappingValueRepository->paginateLatestGroupedByKey($filters, $sortColumn, $sortOrder, $perPage),
        ];
    }

    public function getByKey(string $key): array
    {
        $mappingValue = $this->mappingValueRepository->findByKey($key);

        if ($mappingValue->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Data not found',
                'status' => 404,
            ];
        }

        return [
            'success' => true,
            'message' => 'Data found',
            'status' => 200,
            'data' => $mappingValue,
        ];
    }

    public function getProvince(array $filters): array
    {
        try {
            $query = Province::query();

            if (! empty($filters['code'])) {
                $query->where('code', $filters['code']);
            }

            if (! empty($filters['name'])) {
                $query->where('name', 'like', '%'.$filters['name'].'%');
            }

            if (! empty($filters['name'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['name']));
            }

            if (! empty($filters['code'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['code']));
            }

            return [
                'success' => true,
                'message' => 'Data master wilayah berhasil diambil.',
                'status' => 200,
                'data' => $query->select('code', 'name')->get(),
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function getRegency(array $filters): array
    {
        try {
            $query = Regency::query();

            if (! empty($filters['code'])) {
                $query->where('code', $filters['code']);
            }

            if (! empty($filters['name'])) {
                $query->where('name', 'like', '%'.$filters['name'].'%');
            }

            if (! empty($filters['province_code'])) {
                $query->where('province_code', $filters['province_code']);
            }

            if (! empty($filters['name'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['name']));
            }

            if (! empty($filters['code'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['code']));
            }

            if (! empty($filters['province_code'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['province_code']));
            }

            return [
                'success' => true,
                'message' => 'Data master wilayah berhasil diambil.',
                'status' => 200,
                'data' => $query->get(),
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function getDistrict(array $filters): array
    {
        try {
            $query = District::query();

            if (! empty($filters['code'])) {
                $query->where('code', $filters['code']);
            }

            if (! empty($filters['name'])) {
                $query->where('name', 'like', '%'.$filters['name'].'%');
            }

            if (! empty($filters['regency_code'])) {
                $query->where('regency_code', $filters['regency_code']);
            }

            if (! empty($filters['province_code'])) {
                $query->where('province_code', $filters['province_code']);
            }

            if (! empty($filters['name'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['name']));
            }

            if (! empty($filters['regency_code'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['regency_code']));
            }

            if (! empty($filters['province_code'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['province_code']));
            }

            if (! empty($filters['district_code'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['district_code']));
            }

            return [
                'success' => true,
                'message' => 'Data master wilayah berhasil diambil.',
                'status' => 200,
                'data' => $query->get(),
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function getVillage(array $filters): array
    {
        try {
            $query = Village::query();

            if (! empty($filters['code'])) {
                $query->where('code', $filters['code']);
            }

            if (! empty($filters['name'])) {
                $query->where('name', 'like', '%'.$filters['name'].'%');
            }

            if (! empty($filters['district_code'])) {
                $query->where('district_code', $filters['district_code']);
            }

            if (! empty($filters['name'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['name']));
            }

            if (! empty($filters['code'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['code']));
            }

            if (! empty($filters['district_code'])) {
                $query->orWhere(fn (Builder $q) => $q->search((string) $filters['district_code']));
            }

            return [
                'success' => true,
                'message' => 'Data master wilayah berhasil diambil.',
                'status' => 200,
                'data' => $query->get(),
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function getListDataInstitutionMappingValue(): array
    {
        $mapping = $this->mappingValueRepository->getInstitutionMappingValues();

        return [
            'success' => true,
            'message' => 'Data institution mapping berhasil diambil.',
            'status' => 200,
            'data' => [
                'industry_type' => $mapping->where('key', 'industry_type')->values(),
                'income_type' => $mapping->where('key', 'income_type')->values(),
                'institution_type' => $mapping->where('key', 'institution_type')->values(),
                'job_id' => $mapping->where('key', 'job_id')->values(),
                'tax_type' => $mapping->where('key', 'tax_type')->values(),
                'id_type' => $mapping->where('key', 'id_type')->values(),
                'currency' => $mapping->where('key', 'currency')->values(),
            ],
        ];
    }

    public function getLampiranMapping(array $payload): array
    {
        $settingKey = match ($payload['module']) {
            'penjaminan' => 'PENJAMINAN_SETTINGS',
            'claim' => 'KLAIM_SETTINGS',
            default => $payload['module'],
        };

        return [
            'success' => true,
            'message' => 'Data lampiran mapping berhasil diambil.',
            'status' => 200,
            'data' => $this->mappingValueRepository->getMandatoryLampiran(
                (string) $payload['jenis_mitra'],
                $settingKey,
                (string) $payload['jenis_produk'],
            ),
        ];
    }

}
