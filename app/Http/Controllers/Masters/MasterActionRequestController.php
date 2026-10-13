<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreMasterReworkRequest;
use App\Models\MasterActionRequest;
use App\Services\Masters\MasterActionRequestService;
use App\Services\Masters\MasterReworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterActionRequestController extends Controller
{
    public function __construct(
        private readonly MasterActionRequestService $service,
        private readonly MasterReworkService $reworkService,
    ) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', MasterActionRequest::STATUS_PENDING);
        abort_unless(in_array($status, [
            MasterActionRequest::STATUS_PENDING,
            MasterActionRequest::STATUS_APPROVED,
            MasterActionRequest::STATUS_REJECTED,
            'all',
        ], true), 404);

        $actions = MasterActionRequest::query()
            ->with('masterRequest')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('master_action_requests.index', compact('actions', 'status'));
    }

    public function show(MasterActionRequest $master_action_request): View
    {
        $action = $master_action_request->load([
            'masterRequest.line',
            'selectedFolios',
            'resultingMasterRequest.folios',
            'resultingPrintBatch',
        ]);

        return view('master_action_requests.show', compact('action'));
    }

    public function approve(Request $request, MasterActionRequest $master_action_request): RedirectResponse
    {
        if ($master_action_request->action_type === MasterActionRequest::TYPE_REWORK) {
            return redirect()->route(
                $master_action_request->isPending()
                    ? 'master_action_requests.rework.create'
                    : 'master_action_requests.show',
                $master_action_request,
            );
        }

        $action = $this->service->approve($master_action_request, $request->user());

        return redirect()
            ->route('master_action_requests.show', $action)
            ->with('success', "Solicitud #{$action->id} confirmada.");
    }

    public function createRework(MasterActionRequest $master_action_request): View
    {
        abort_unless(
            $master_action_request->action_type === MasterActionRequest::TYPE_REWORK
                && $master_action_request->isPending(),
            404,
        );

        $action = $master_action_request->load(['masterRequest', 'selectedFolios']);

        return view('master_reworks.create', [
            ...$this->reworkService->buildCreateFormData($action->masterRequest),
            'action' => $action,
        ]);
    }

    public function storeRework(
        StoreMasterReworkRequest $request,
        MasterActionRequest $master_action_request,
    ): RedirectResponse {
        abort_unless($master_action_request->action_type === MasterActionRequest::TYPE_REWORK, 404);

        $action = $this->service->approve(
            $master_action_request,
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route('master_reworks.show', $action->resulting_master_request_id)
            ->with('success', "Solicitud #{$action->id} confirmada. Revisa los folios y sus cantidades antes de imprimir.");
    }

    public function reject(
        Request $request,
        MasterActionRequest $master_action_request,
    ): RedirectResponse {
        $notes = $request->input('review_notes');
        $request->merge([
            'review_notes' => is_scalar($notes) ? trim(strip_tags((string) $notes)) : null,
        ]);
        $data = $request->validate([
            'review_notes' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $action = $this->service->reject(
            $master_action_request,
            $request->user(),
            $data['review_notes'],
        );

        return redirect()
            ->route('master_action_requests.show', $action)
            ->with('success', "Solicitud #{$action->id} rechazada.");
    }
}
