<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internal\Concerns\RespondsWithServiceResult;
use App\Http\Requests\Notif\CountNotifRequest;
use App\Http\Requests\Notif\GetNotifRequest;
use App\Http\Requests\Notif\UpdateAllNotifRequest;
use App\Http\Requests\Notif\UpdateNotifRequest;
use App\Services\NotifService;

class NotifInternalController extends Controller
{
    use RespondsWithServiceResult;

    public function __construct(
        protected NotifService $notifService,
    ) {
    }

    public function getNotif(GetNotifRequest $request)
    {
        return $this->respond($this->notifService->getNotif($request->validated()));
    }

    public function countNotif(CountNotifRequest $request)
    {
        return $this->respond($this->notifService->countNotif($request->validated()));
    }

    public function update(UpdateNotifRequest $request)
    {
        return $this->respond($this->notifService->update($request->validated()));
    }

    public function updateAllNotif(UpdateAllNotifRequest $request)
    {
        return $this->respond($this->notifService->updateAllNotif($request->validated()));
    }
}
