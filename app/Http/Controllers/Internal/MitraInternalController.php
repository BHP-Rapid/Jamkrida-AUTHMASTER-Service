<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internal\Concerns\RespondsWithServiceResult;
use App\Http\Requests\Mitra\MitraLookupRequest;
use App\Http\Requests\Mitra\MitraTableRequest;
use App\Http\Requests\Mitra\StoreMitraRequest;
use App\Http\Requests\Mitra\UpdateMitraRequest;
use App\Services\MitraService;

class MitraInternalController extends Controller
{
    use RespondsWithServiceResult;

    public function __construct(
        protected MitraService $mitraService,
    ) {
    }

    public function getDataByMitraId(MitraLookupRequest $request)
    {
        return $this->respond($this->mitraService->getDataByMitraId($request->validated()));
    }

    public function getMitraFromCreatio(MitraTableRequest $request)
    {
        return $this->respond($this->mitraService->getMitraFromCreatio($request->validated()));
    }

    public function getDataMitra()
    {
        return $this->respond($this->mitraService->getDataMitra());
    }

    public function store(StoreMitraRequest $request)
    {
        return $this->respond($this->mitraService->store($request->validated()));
    }

    public function updateByMitraId(UpdateMitraRequest $request)
    {
        return $this->respond($this->mitraService->updateByMitraId($request->validated()));
    }
}
