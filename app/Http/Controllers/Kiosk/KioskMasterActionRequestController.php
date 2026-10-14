<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kiosk\StoreKioskMasterReworkRequest;
use App\Models\MasterActionRequest;
use App\Models\MasterRequest;
use App\Models\User;
use App\Services\Kiosk\KioskRequisitionPrintService;
use App\Services\Masters\MasterActionRequestService;
use App\Services\Masters\MasterReworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KioskMasterActionRequestController extends Controller
{
    public function __construct(
        private readonly MasterActionRequestService $service,
        private readonly KioskRequisitionPrintService $printService,
        private readonly MasterReworkService $reworkService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $kioskUser */
        $kioskUser = $request->attributes->get('kiosk_user');

        $actions = MasterActionRequest::query()
            ->with(['masterRequest', 'selectedFolios'])
            ->where('requested_by_user_id', $kioskUser->id)
            ->latest('id')
            ->paginate(15);

        return view('kiosk.master-action-requests.index', compact('actions'));
    }

    public function create(Request $request): View
    {
        $type = (string) $request->query('type', MasterActionRequest::TYPE_REWORK);
        abort_unless(in_array($type, [MasterActionRequest::TYPE_REWORK, MasterActionRequest::TYPE_REPRINT], true), 404);

        $query = trim((string) $request->query('q', ''));
        $query = mb_substr($query, 0, 80);
        $sourceId = $request->integer('source_id');

        $source = $sourceId > 0 ? $this->service->findSelectableSource($sourceId, $type) : null;

        if ($type === MasterActionRequest::TYPE_REWORK && $source) {
            return view('master_reworks.create', [
                ...$this->reworkService->buildCreateFormData($source),
                'kioskMode' => true,
                'action' => null,
            ]);
        }

        return view('kiosk.master-action-requests.create', [
            'type' => $type,
            'query' => $query,
            'sources' => $query !== '' ? $this->service->searchSources($query, $type) : null,
            'source' => $source,
        ]);
    }

    public function storeRework(
        StoreKioskMasterReworkRequest $request,
        MasterRequest $master_request,
    ): RedirectResponse {
        /** @var User $kioskUser */
        $kioskUser = $request->attributes->get('kiosk_user');
        $action = DB::transaction(function () use ($request, $master_request, $kioskUser): MasterActionRequest {
            $action = $this->service->submitPreparedRework(
                $master_request,
                $request->validated(),
                $kioskUser,
            );
            $this->printService->prepareMasterAction($action, $kioskUser);

            return $action;
        });

        return redirect()
            ->route('kiosk.dashboard')
            ->with('success', "Retrabajo Master #{$action->id} enviado a Label Room para validación.")
            ->with('kiosk_receipt', [
                'type' => 'Retrabajo Master',
                'request_kind' => 'master_action',
                'request_id' => $action->id,
                'created_at' => now(config('kiosk.display_timezone'))->format('d/m/Y H:i'),
            ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $reason = $request->input('reason');
        $request->merge([
            'reason' => is_scalar($reason) ? trim(strip_tags((string) $reason)) : null,
        ]);

        $data = $request->validate([
            'action_type' => ['required', Rule::in([MasterActionRequest::TYPE_REPRINT])],
            'master_request_id' => ['required', 'integer', 'exists:master_requests,id'],
            'folio_ids' => ['required', 'array', 'min:1'],
            'folio_ids.*' => ['required', 'integer', 'distinct', 'exists:master_request_folios,id'],
            'copies' => ['required', 'integer', 'min:1', 'max:20'],
            'additional_folios_count' => ['prohibited'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        /** @var User $kioskUser */
        $kioskUser = $request->attributes->get('kiosk_user');
        $action = DB::transaction(function () use ($data, $kioskUser): MasterActionRequest {
            $action = $this->service->submit($data, $kioskUser);
            $this->printService->prepareMasterAction($action, $kioskUser);

            return $action;
        });

        return redirect()
            ->route('kiosk.dashboard')
            ->with('success', "Solicitud de {$action->typeLabel()} Master #{$action->id} enviada a Label Room para revisión.")
            ->with('kiosk_receipt', [
                'type' => "Solicitud de {$action->typeLabel()} Master",
                'request_kind' => 'master_action',
                'request_id' => $action->id,
                'created_at' => now(config('kiosk.display_timezone'))->format('d/m/Y H:i'),
            ]);
    }
}
