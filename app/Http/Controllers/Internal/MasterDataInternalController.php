<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internal\Concerns\RespondsWithServiceResult;
use App\Http\Requests\Master\LampiranMappingRequest;
use App\Http\Requests\Master\MappingValueTableRequest;
use App\Http\Requests\Master\RegionLookupRequest;
use App\Services\MappingValueService;

class MasterDataInternalController extends Controller
{
    use RespondsWithServiceResult;

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
}
