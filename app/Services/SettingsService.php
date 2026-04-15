<?php

namespace App\Services;

use App\Models\SettingProductDetail;
use App\Repositories\SettingProductDetailRepository;
use App\Repositories\SettingsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsService
{
    public function __construct(
        protected SettingsRepository $settingsRepository,
        protected SettingProductDetailRepository $settingProductDetailRepository,
    ) {
    }

    public function index(): array
    {
        return [
            'success' => true,
            'status' => 200,
            'data' => $this->settingsRepository->getAllWithProductDetails(),
        ];
    }

    public function show(array $payload): array
    {
        try {
            $settings = $this->settingsRepository->findProductSettings(
                (string) $payload['module'],
                (string) $payload['mitra_id'],
                (string) $payload['product_id'],
            );

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data settings berhasil diambil',
                'data' => $settings,
            ];
        } catch (\Throwable $exception) {
            Log::error('Settings detail retrieval failed', [
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
                'message' => 'Terjadi kesalahan saat mengambil data settings',
                'errors' => ['exception' => [$exception->getMessage()]],
            ];
        }
    }

    public function showSettingsMenu(): array
    {
        try {
            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data settings berhasil diambil',
                'data' => $this->settingsRepository->getGeneralMenuSettings(),
            ];
        } catch (\Throwable $exception) {
            Log::error('Settings menu retrieval failed', [
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
                'message' => 'Terjadi kesalahan saat mengambil data settings',
                'errors' => ['exception' => [$exception->getMessage()]],
            ];
        }
    }

    public function getLampiranSettingsMenu(array $payload): array
    {
        $settingKey = match ($payload['module']) {
            'penjaminan' => 'PENJAMINAN_SETTINGS',
            'claim' => 'KLAIM_SETTINGS',
            default => $payload['module'],
        };

        try {
            return [
                'success' => true,
                'status' => 200,
                'data' => $this->settingsRepository->getLampiranSettingsMenu(
                    (string) $payload['jenis_mitra'],
                    $settingKey,
                    (string) $payload['jenis_produk'],
                ),
            ];
        } catch (\Throwable $exception) {
            Log::error('Lampiran settings menu retrieval failed', [
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
                'message' => 'Terjadi kesalahan saat mengambil data settings',
                'errors' => ['exception' => [$exception->getMessage()]],
            ];
        }
    }

    public function showGeneralSettings(): array
    {
        try {
            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data settings berhasil diambil',
                'data' => $this->settingsRepository->getGeneralSettings(),
            ];
        } catch (\Throwable $exception) {
            Log::error('General settings retrieval failed', [
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
                'message' => 'Terjadi kesalahan saat mengambil data settings',
                'errors' => ['exception' => [$exception->getMessage()]],
            ];
        }
    }

    public function getSettingsByMitraId(string $mitraId): array
    {
        try {
            return [
                'success' => true,
                'status' => 200,
                'message' => 'Data settings berhasil diambil',
                'data' => $this->settingsRepository->getByMitraId($mitraId),
            ];
        } catch (\Throwable $exception) {
            Log::error('Settings by mitra retrieval failed', [
                'mitra_id' => $mitraId,
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
                'message' => 'Gagal mendapatkan settings',
                'errors' => ['exception' => [$exception->getMessage()]],
            ];
        }
    }

    public function update(array $payload): array
    {
        DB::beginTransaction();

        try {
            $updatedDetails = [];
            $totalUpdatedRows = 0;

            foreach ($payload as $item) {
                $updatedRows = $this->settingsRepository->updateMandatoryFlag($item);
                $totalUpdatedRows += $updatedRows;

                if ($updatedRows > 0) {
                    $updatedData = $this->settingsRepository->findUpdatedMandatoryDetail($item);

                    if ($updatedData !== null) {
                        $updatedDetails[] = $updatedData;
                    }
                }
            }

            if ($totalUpdatedRows === 0) {
                DB::rollBack();

                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'Data tidak ditemukan atau tidak ada perubahan',
                ];
            }

            DB::commit();

            Log::info('Settings updated successfully', [
                'total_updated_rows' => $totalUpdatedRows,
                'payload_count' => count($payload),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Settings berhasil diupdate',
                'data' => [
                    'total_updated_rows' => $totalUpdatedRows,
                    'updated_details' => $updatedDetails,
                ],
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Settings update failed', [
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
                'message' => 'Gagal mengupdate settings',
                'errors' => ['exception' => [$exception->getMessage()]],
            ];
        }
    }

    public function updateMandatory(array $payload): array
    {
        DB::beginTransaction();

        try {
            $mitra = (string) ($payload['mitra_id'] ?? '');

            if (! empty($payload['generalSettings']) && is_array($payload['generalSettings'])) {
                $this->handleGeneralSettings($mitra, $payload['generalSettings']);
            }

            if (! empty($payload['lampiran']) && is_array($payload['lampiran'])) {
                foreach ($payload['lampiran'] as $product => $itemsMap) {
                    if (! is_array($itemsMap)) {
                        continue;
                    }

                    $this->handleProductModuleFromMap(
                        $mitra,
                        'PENJAMINAN_SETTINGS',
                        (string) $product,
                        $itemsMap,
                        false,
                    );
                }
            }

            if (! empty($payload['reasonClaim']) && is_array($payload['reasonClaim'])) {
                foreach ($payload['reasonClaim'] as $product => $itemsMap) {
                    if (! is_array($itemsMap)) {
                        continue;
                    }

                    $this->handleProductModuleFromMap(
                        $mitra,
                        'KLAIM_SETTINGS',
                        (string) $product,
                        $itemsMap,
                        true,
                    );
                }
            }

            DB::commit();

            Log::info('Settings mandatory updated successfully', [
                'mitra_id' => $mitra,
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Setting updated successfully',
                'details' => $payload,
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Settings mandatory update failed', [
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
                'message' => 'Gagal mengupdate settings',
                'errors' => ['exception' => [$exception->getMessage()]],
            ];
        }
    }

    public function store(array $payload): array
    {
        DB::beginTransaction();

        try {
            $setting = $this->settingsRepository->create([
                'mitra_id' => $payload['mitra_id'],
                'module' => $payload['module'],
            ]);

            $productSettingsDetails = [];

            foreach ($payload['product_details'] as $detail) {
                $productSettingsDetails[] = [
                    'hdr_id' => $setting->id,
                    'product_id' => $detail['product_id'],
                    'key' => $detail['key'],
                    'value' => $detail['value'] ?? null,
                    'lampiran' => $detail['lampiran'] ?? null,
                    'reason_claim' => $detail['reason_claim'] ?? null,
                    'is_mandatory' => $detail['is_mandatory'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('setting_product_dtl')->insert($productSettingsDetails);

            DB::commit();

            Log::info('Settings created successfully', [
                'setting_id' => $setting->id,
                'mitra_id' => $setting->mitra_id,
                'module' => $setting->module,
                'product_details_count' => count($productSettingsDetails),
                'actor_user_id' => request()->user()?->user_id,
                'actor_role' => request()->user()?->role,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Settings berhasil dibuat',
                'data' => [
                    'setting' => $setting,
                    'product_details_count' => $payload['product_details'],
                ],
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Settings create failed', [
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
                'message' => 'Gagal membuat settings',
                'errors' => ['exception' => [$exception->getMessage()]],
            ];
        }
    }

    private function handleGeneralSettings(string $mitra, array $itemsToUpdate): void
    {
        $module = 'GENERAL_SETTINGS';
        $settingHdr = $this->settingsRepository->firstOrCreateHeader($mitra, $module);

        foreach ($itemsToUpdate as $key => $value) {
            $detail = $this->settingsRepository->findGeneralSettingDetail($settingHdr->id, (string) $key);

            if ($detail instanceof SettingProductDetail) {
                $detail->value = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
                $detail->is_mandatory = 1;
                $detail->save();
                continue;
            }

            $this->settingsRepository->createDetail([
                'hdr_id' => $settingHdr->id,
                'product_id' => '',
                'key' => (string) $key,
                'value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value),
                'lampiran' => '',
                'reason_claim' => '',
                'is_mandatory' => 1,
            ]);
        }
    }

    private function handleProductModuleFromMap(
        string $mitra,
        string $module,
        string $product,
        array $itemsMap,
        bool $isReasonClaim,
    ): void {
        $moduleKey = is_string($module) ? $module : 'default_module';
        $settingHdr = $this->settingsRepository->firstOrCreateHeader($mitra, $moduleKey);

        foreach ($itemsMap as $detailId => $flag) {
            $detailId = (int) $detailId;
            $value = $flag ? 1 : 0;

            $detail = $this->settingsRepository->findProductModuleDetailById(
                $detailId,
                $settingHdr->id,
                $product,
            );

            if ($detail instanceof SettingProductDetail) {
                if ($isReasonClaim) {
                    if ((string) $detail->reason_claim !== (string) $value) {
                        $detail->reason_claim = $value;
                        $detail->save();
                    }

                    continue;
                }

                if ((int) $detail->is_mandatory !== $value) {
                    $detail->is_mandatory = $value;
                    $detail->save();
                }

                continue;
            }

            $this->settingsRepository->createDetail([
                'hdr_id' => $settingHdr->id,
                'product_id' => $product,
                'key' => $moduleKey,
                'value' => '',
                'lampiran' => null,
                'reason_claim' => $isReasonClaim ? $value : 0,
                'is_mandatory' => $isReasonClaim ? 0 : $value,
            ]);
        }
    }
}
