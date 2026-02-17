<?php

namespace App\Http\Controllers;

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
        $mr = $this->service->loadRequestWithBatches($master_request);

        return view('master_reprints.index', compact('mr'));
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
