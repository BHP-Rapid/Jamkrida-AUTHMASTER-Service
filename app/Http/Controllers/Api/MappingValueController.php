<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\LampiranMappingRequest;
use App\Http\Requests\Master\MappingValueTableRequest;
use App\Http\Requests\Master\RegionLookupRequest;
use App\Services\MappingValueService;

class MappingValueController extends Controller
{
    public function __construct(
        protected MappingValueService $mappingValueService,
    ) {
    }

    public function index()
    {
        return $this->respond($this->mappingValueService->index());
    }

    public function indexTableMapping(MappingValueTableRequest $request)
    {
        return $this->respond($this->mappingValueService->indexTableMapping($request->validated()));
    }

    public function getByKey(string $key)
    {
        return $this->respond($this->mappingValueService->getByKey($key));
    }

    public function getProvince(RegionLookupRequest $request)
    {
        return $this->respond($this->mappingValueService->getProvince($request->validated()));
    }

    public function getRegency(RegionLookupRequest $request)
    {
        return $this->respond($this->mappingValueService->getRegency($request->validated()));
    }

    public function getDistrict(RegionLookupRequest $request)
    {
        return $this->respond($this->mappingValueService->getDistrict($request->validated()));
    }

    public function getVillage(RegionLookupRequest $request)
    {
        return $this->respond($this->mappingValueService->getVillage($request->validated()));
    }

    public function getListDataInstitutionMappingValue()
    {
        return $this->respond($this->mappingValueService->getListDataInstitutionMappingValue());
    }

    public function getLampiranMapping(LampiranMappingRequest $request)
    {
        return $this->respond($this->mappingValueService->getLampiranMapping($request->validated()));
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
            message: $result['message'],
            status: $result['status'],
        );
    }
}
