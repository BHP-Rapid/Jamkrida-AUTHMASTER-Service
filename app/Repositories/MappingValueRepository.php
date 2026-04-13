<?php

namespace App\Repositories;

use App\Models\MappingValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MappingValueRepository
{
    public function all(): Collection
    {
        return MappingValue::query()->get();
    }

    public function paginateLatestGroupedByKey(array $filters, string $sortColumn, string $sortOrder, int $perPage): LengthAwarePaginator
    {
        $query = MappingValue::query()
            ->whereIn('id', function ($subQuery): void {
                $subQuery->select(DB::raw('MAX(id)'))
                    ->from('mapping_value')
                    ->groupBy('key');
            });

        if (! empty($filters['key'])) {
            $query->where('key', 'like', '%'.$filters['key'].'%');
        }

        return $query
            ->orderBy($sortColumn, $sortOrder)
            ->paginate($perPage);
    }

    public function findByKey(string $key): Collection
    {
        return MappingValue::query()
            ->where('key', $key)
            ->get();
    }

    public function getInstitutionMappingValues(): Collection
    {
        return MappingValue::query()
            ->whereIn('key', [
                'industry_type',
                'income_type',
                'institution_type',
                'job_id',
                'tax_type',
                'id_type',
                'currency',
            ])
            ->select('id', 'parent_id', 'sequence', 'key', 'value', 'label')
            ->orderBy('key')
            ->orderBy('sequence')
            ->get();
    }

    public function getMandatoryLampiran(string $jenisMitra, string $module, string $jenisProduk): Collection
    {
        return DB::table('setting_hdr as a')
            ->join('setting_product_dtl as b', 'a.id', '=', 'b.hdr_id')
            ->join('mapping_value as c', 'b.lampiran', '=', 'c.value')
            ->where('c.key', 'lampiran')
            ->where('b.key', $module)
            ->where('a.module', $module)
            ->where('a.mitra_id', strtolower($jenisMitra))
            ->where('b.product_id', strtolower($jenisProduk))
            ->where('b.is_mandatory', 1)
            ->select('c.value', 'c.label', 'c.option1 as detail', 'c.option2 as type')
            ->get();
    }
}
