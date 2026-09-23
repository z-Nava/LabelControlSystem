<?php

namespace App\Http\Controllers\Oracle;

use App\Http\Controllers\Controller;
use App\Http\Requests\Oracle\ImportOracleJobsRequest;
use App\Services\Oracle\OracleJobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OracleJobController extends Controller
{
    public function __construct(private readonly OracleJobService $service)
    {
    }

    public function index(): View
    {
        $filters = request()->validate([
            'job_number' => ['nullable', 'string', 'max:40'],
            'line' => ['nullable', 'string', 'max:30'],
            'job_status' => ['nullable', 'string', 'max:40'],
            'assembly' => ['nullable', 'string', 'max:80'],
            'job_qty' => ['nullable', 'integer'],
            'ship_to' => ['nullable', 'string', 'max:160'],
            'ship_code' => ['nullable', 'string', 'max:80'],
            'ttl_cust_po' => ['nullable', 'string', 'max:80'],
        ]);
        $jobs = $this->service->paginate(20, $filters);

        return view('oracle_jobs.index', compact('jobs', 'filters'));
    }

    public function importView(): View
    {
        return view('oracle_jobs.import');
    }

    public function import(ImportOracleJobsRequest $request): RedirectResponse
    {
        $result = $this->service->importFromExcel($request->file('file'));

        return redirect()->route('oracle_jobs.index')
            ->with('success', "Importación OK. Insertados: {$result['inserted']}, Actualizados: {$result['updated']}, Omitidos: {$result['skipped']}.");
    }
}
