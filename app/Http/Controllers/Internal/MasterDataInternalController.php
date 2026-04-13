<?php

namespace App\Http\Controllers\Internal;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\LampiranMappingRequest;
use App\Http\Requests\Master\MappingValueTableRequest;
use App\Http\Requests\Master\RegionLookupRequest;
use App\Services\MappingValueService;

class MasterDataInternalController extends Controller
{
    public function __construct(
        protected MappingValueService $mappingValueService,
    ) {
    }

    public function mappingValues()
    {
        return $this->respond($this->mappingValueService->index());
    }

    public function mappingValuesTable(MappingValueTableRequest $request)
    {
        return $this->respond($this->mappingValueService->indexTableMapping($request->validated()));
    }

    public function mappingValuesByKey(string $key)
    {
        return $this->respond($this->mappingValueService->getByKey($key));
    }

    public function institutionMappings()
    {
        return $this->respond($this->mappingValueService->getListDataInstitutionMappingValue());
    }

    public function lampiranMappings(LampiranMappingRequest $request)
    {
        return $this->respond($this->mappingValueService->getLampiranMapping($request->validated()));
    }

    public function provinces(RegionLookupRequest $request)
    {
        return $this->respond($this->mappingValueService->getProvince($request->validated()));
    }

    public function regencies(RegionLookupRequest $request)
    {
        return $this->respond($this->mappingValueService->getRegency($request->validated()));
    }

    public function districts(RegionLookupRequest $request)
    {
        return $this->respond($this->mappingValueService->getDistrict($request->validated()));
    }

    public function villages(RegionLookupRequest $request)
    {
        return $this->respond($this->mappingValueService->getVillage($request->validated()));
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
