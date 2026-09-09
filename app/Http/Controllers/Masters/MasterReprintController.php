<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\MasterRequest;
use App\Services\Masters\MasterReprintService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterReprintController extends Controller
{
    public function __construct(private readonly MasterReprintService $service)
    {
    }

    public function index(MasterRequest $master_request): View
    {
        return view('master_reprints.index', $this->service->buildHistoryData($master_request));
    }

    public function search(Request $request): View
    {
        $job = trim((string) $request->query('job', ''));

        $masterRequests = $this->service->searchByJob($job);

        return view('master_reprints.search', [
            'masterRequests' => $masterRequests,
            'job' => $job,
        ]);
    }
}
