<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use App\Models\Shift;
use App\Services\Masters\MasterRequestService;
use App\Models\MasterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterRequestController extends Controller
{
    public function __construct(private readonly MasterRequestService $service)
    {
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending');
        $q = trim((string) $request->query('q', ''));

        $masterRequests = MasterRequest::query()
            ->with(['line', 'shift'])
            ->withCount([
                'folios as total_folios',
                'folios as printed_folios' => fn ($query) => $query->where('status', 'printed'),
            ])
            ->when($status === 'pending', fn ($query) => $query->whereIn('status', ['requested', 'in_progress']))
            ->when($status === 'completed', fn ($query) => $query->where('status', 'completed'))
            ->when($status === 'cancelled', fn ($query) => $query->where('status', 'cancelled'))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('id', $q)
                        ->orWhere('leader_name', 'like', "%{$q}%")
                        ->orWhere('job_assembly', 'like', "%{$q}%")
                        ->orWhere('job_packaging', 'like', "%{$q}%")
                        ->orWhere('po_number', 'like', "%{$q}%");
                });
            })
            ->latest('request_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('master_requests.index', [
            'masterRequests' => $masterRequests,
            'filters' => [
                'status' => $status,
                'q' => $q,
            ],
        ]);
    }


    public function create(): View
    {
        $lines = ProductionLine::where('active', true)->orderBy('code')->get();
        $shifts = Shift::where('active', true)->orderBy('code')->get();

        return view('master_requests.create', compact('lines', 'shifts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'request_date' => ['required', 'date'],
            'week' => ['required', 'integer', 'min:1', 'max:53'],
            'line_id' => ['required', 'exists:production_lines,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'leader_name' => ['required', 'string', 'max:120'],

            'po_number' => ['nullable', 'string', 'max:80'],
            'job_assembly' => ['nullable', 'string', 'max:40'],
            'job_packaging' => ['nullable', 'string', 'max:40'],
            'destination' => ['nullable', 'string', 'max:80'],

            'folios_from' => ['required', 'integer', 'min:1'],
            'folios_to' => ['required', 'integer', 'min:1'],
            'std_pack_qty' => ['nullable', 'integer', 'min:1'],

            'partial_folio' => ['nullable', 'integer', 'min:1'],
            'partial_qty' => ['nullable', 'integer', 'min:1'],

            'request_type' => ['required', 'string', 'max:40'],
            'kind' => ['required', 'in:new,reposition'],

            'notes' => ['nullable', 'string'],
        ]);

        // ✅ trazabilidad (del sistema, no del form)
        $data['requested_by_user_id'] = auth()->id();
        $data['requested_by_name'] = auth()->user()->name;

        $mr = $this->service->create($data);

        return redirect()
            ->route('master_requests.show', $mr)
            ->with('success', 'Requisición Master creada.');
    }


    public function show($id): View
    {
        $mr = MasterRequest::with(['line','shift','folios'])->findOrFail($id);

        return view('master_requests.show', compact('mr'));
    }

    // Endpoint para autollenar (AJAX)
    public function lookup(Request $request)
    {
        $request->validate(['job_number' => ['required','string','max:40']]);

        return response()->json(
            $this->service->lookupOracleJob($request->string('job_number')->toString())
        );
    }
}
