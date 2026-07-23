<?php

namespace App\Services\Dashboard;

use App\Models\DummyRequest;
use App\Models\LabelRequest;
use App\Models\MasterRequest;
use App\Models\User;

class PendingRequestCountService
{
    /**
     * @return array{master: int, labels: int, dummy: int}
     */
    public function for(User $user): array
    {
        return [
            'master' => $user->hasModuleAccess('master')
                ? MasterRequest::query()->where('status', MasterRequest::STATUS_REQUESTED)->count()
                : 0,
            'labels' => $user->hasModuleAccess('labels')
                ? LabelRequest::query()->where('status', LabelRequest::STATUS_REQUESTED)->count()
                : 0,
            'dummy' => $user->hasModuleAccess('dummy')
                ? DummyRequest::query()->where('status', DummyRequest::STATUS_REQUESTED)->count()
                : 0,
        ];
    }
}
