<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LampiranSettingsMenuRequest;
use App\Http\Requests\Settings\ShowSettingsRequest;
use App\Http\Requests\Settings\StoreSettingsRequest;
use App\Http\Requests\Settings\UpdateMandatorySettingsRequest;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService,
    ) {
    }

    public function index()
    {
        return $this->respond($this->settingsService->index());
    }

    public function show(ShowSettingsRequest $request)
    {
        return $this->respond($this->settingsService->show($request->validated()));
    }

    public function showSettingsMenu()
    {
        return $this->respond($this->settingsService->showSettingsMenu());
    }

    public function getLampiranSettingsMenu(LampiranSettingsMenuRequest $request)
    {
        return $this->respond($this->settingsService->getLampiranSettingsMenu($request->validated()));
    }

    public function showGeneralSettings()
    {
        return $this->respond($this->settingsService->showGeneralSettings());
    }

    public function getSettingsByMitraId(string $mitraId)
    {
        return $this->respond($this->settingsService->getSettingsByMitraId($mitraId));
    }

    public function store(StoreSettingsRequest $request)
    {
        return $this->respond($this->settingsService->store($request->validated()));
    }

    public function update(UpdateSettingsRequest $request)
    {
        return $this->respond($this->settingsService->update($request->validated()));
    }

    public function updateMandatory(UpdateMandatorySettingsRequest $request)
    {
        $result = $this->settingsService->updateMandatory($request->validated());

        if (isset($result['details'])) {
            $result['data'] = [
                'details' => $result['details'],
            ];
            unset($result['details']);
        }

        return $this->respond($result);
    }

    protected function respond(array $result)
    {
        if (! $result['success']) {
            return ApiResponse::error(
                message: $result['message'],
                status: $result['status'],
                errors: $result['errors'] ?? [],
                data: $result['data'] ?? null,
            );
        }

        return ApiResponse::success(
            data: $result['data'] ?? null,
            message: $result['message'] ?? 'Request berhasil diproses.',
            status: $result['status'],
        );
    }
}
