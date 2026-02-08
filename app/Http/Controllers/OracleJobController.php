<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportOracleJobsRequest;
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
        $filters = request()->only(['q', 'line', 'job_status']);
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
