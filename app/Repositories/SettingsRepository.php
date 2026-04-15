<?php

namespace App\Repositories;

use App\Models\SettingProductDetail;
use App\Models\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class SettingsRepository
{
    public function getAllWithProductDetails(): Collection
    {
        return Settings::query()
            ->with(['productDetails' => function ($query): void {
                $query->select('id', 'hdr_id', 'product_id', 'lampiran', 'is_mandatory');
            }])
            ->select('id', 'mitra_id', 'module')
            ->get();
    }

    public function create(array $payload): Settings
    {
        return Settings::query()->create($payload);
    }

    public function findProductSettings(
        string $module,
        string $mitraId,
        string $productId,
    ): Collection {
        return DB::table('setting_product_dtl')
            ->join('setting_hdr', 'setting_product_dtl.hdr_id', '=', 'setting_hdr.id')
            ->where('setting_hdr.module', $module)
            ->where('setting_hdr.mitra_id', $mitraId)
            ->where('setting_product_dtl.product_id', $productId)
            ->get();
    }

    public function getGeneralMenuSettings(int $headerId = 10): array
    {
        return DB::table('setting_product_dtl')
            ->join('setting_hdr', 'setting_product_dtl.hdr_id', '=', 'setting_hdr.id')
            ->select('setting_product_dtl.*')
            ->where('setting_product_dtl.hdr_id', $headerId)
            ->get()
            ->map(function (object $item): array {
                return [
                    'key' => $item->key,
                    'value' => $item->value,
                ];
            })
            ->all();
    }

    public function getLampiranSettingsMenu(
        string $jenisMitra,
        string $settingKey,
        string $jenisProduk,
    ): Collection {
        return DB::table('setting_hdr as a')
            ->join('setting_product_dtl as b', 'a.id', '=', 'b.hdr_id')
            ->join('mapping_value as c', 'b.lampiran', '=', 'c.value')
            ->where('c.key', 'lampiran')
            ->where('b.key', $settingKey)
            ->where('a.mitra_id', strtolower($jenisMitra))
            ->where('b.product_id', strtolower($jenisProduk))
            ->selectRaw('b.id as dtl_id, a.*, b.*, c.*')
            ->get();
    }

    public function getGeneralSettings(): Collection
    {
        return DB::table('setting_product_dtl')
            ->join('setting_hdr', 'setting_product_dtl.hdr_id', '=', 'setting_hdr.id')
            ->where('setting_hdr.module', 'GENERAL_SETTINGS')
            ->get();
    }

    public function getByMitraId(string $mitraId): Collection
    {
        return Settings::query()
            ->where('mitra_id', $mitraId)
            ->get();
    }

    public function updateMandatoryFlag(array $item): int
    {
        return DB::table('setting_product_dtl')
            ->join('setting_hdr', 'setting_product_dtl.hdr_id', '=', 'setting_hdr.id')
            ->where('setting_hdr.mitra_id', $item['mitra_id'])
            ->where('setting_hdr.module', $item['module'])
            ->where('setting_product_dtl.product_id', $item['product_id'])
            ->where('setting_product_dtl.lampiran', $item['lampiran'])
            ->update([
                'setting_product_dtl.is_mandatory' => $item['is_mandatory'],
                'setting_product_dtl.updated_at' => now(),
            ]);
    }

    public function findUpdatedMandatoryDetail(array $item): ?object
    {
        return DB::table('setting_product_dtl')
            ->join('setting_hdr', 'setting_product_dtl.hdr_id', '=', 'setting_hdr.id')
            ->select('setting_product_dtl.*', 'setting_hdr.mitra_id', 'setting_hdr.module')
            ->where('setting_hdr.module', $item['module'])
            ->where('setting_hdr.mitra_id', $item['mitra_id'])
            ->where('setting_product_dtl.product_id', $item['product_id'])
            ->where('setting_product_dtl.lampiran', $item['lampiran'])
            ->first();
    }

    public function firstOrCreateHeader(string $mitraId, string $module): Settings
    {
        return Settings::query()->firstOrCreate([
            'mitra_id' => $mitraId,
            'module' => $module,
        ]);
    }

    public function findGeneralSettingDetail(int $headerId, string $key): ?SettingProductDetail
    {
        return SettingProductDetail::query()
            ->where('hdr_id', $headerId)
            ->where('key', $key)
            ->first();
    }

    public function findProductModuleDetail(
        int $headerId,
        string $productId,
        string $field,
        string $value,
    ): ?SettingProductDetail {
        return SettingProductDetail::query()
            ->where('hdr_id', $headerId)
            ->where('product_id', $productId)
            ->where($field, $value)
            ->first();
    }

    public function findProductModuleDetailById(
        int $detailId,
        int $headerId,
        string $productId,
    ): ?SettingProductDetail {
        return SettingProductDetail::query()
            ->where('id', $detailId)
            ->where('hdr_id', $headerId)
            ->where('product_id', $productId)
            ->first();
    }

    public function createDetail(array $payload): SettingProductDetail
    {
        return SettingProductDetail::query()->create($payload);
    }
}
