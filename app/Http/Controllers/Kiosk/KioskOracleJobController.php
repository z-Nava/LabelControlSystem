<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kiosk\LookupOracleJobRequest;
use App\Services\Oracle\OracleJobService;
use Illuminate\View\View;

class KioskOracleJobController extends Controller
{
    public function __construct(
        private readonly OracleJobService $service,
    ) {}

    public function index(): View
    {
        return view('kiosk.oracle-job', [
            'hasSearched' => false,
            'job' => null,
            'jobNumber' => '',
        ]);
    }

    public function lookup(LookupOracleJobRequest $request): View
    {
        $jobNumber = $request->string('job_number')->toString();

        return view('kiosk.oracle-job', [
            'hasSearched' => true,
            'job' => $this->service->findByJobNumber($jobNumber),
            'jobNumber' => $jobNumber,
        ]);
    }
}
