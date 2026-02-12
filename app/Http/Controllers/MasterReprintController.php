<?php

namespace App\Http\Controllers;

use App\Models\MasterRequest;
use Illuminate\View\View;
use Illuminate\Http\Request;

class MasterReprintController extends Controller
{
    public function index(MasterRequest $master_request): View
    {
        $mr = $master_request->load([
            'line',
            'shift',
            'printBatches' => fn ($query) => $query
                ->with(['printedBy', 'items.folio'])
                ->orderByDesc('printed_at')
                ->orderByDesc('id'),
        ]);

        return view('master_reprints.index', compact('mr'));
    }

    public function search(Request $request): View
    {
        $job = trim((string) $request->query('job', ''));

        $masterRequests = MasterRequest::query()
            ->whereNotNull('request_type')
            ->with(['line', 'shift'])
            ->withCount('printBatches')
            ->when($job !== '', function ($query) use ($job) {
                $query->where(function ($nested) use ($job) {
                    $nested->where('job_assembly', 'like', "%{$job}%")
                        ->orWhere('job_packaging', 'like', "%{$job}%");
                });
            })
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('master_reprints.search', [
            'masterRequests' => $masterRequests,
            'job' => $job,
        ]);
    }
}
