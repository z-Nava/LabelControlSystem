<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Kiosk\KioskRequisitionPrintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KioskDashboardController extends Controller
{
    public function __construct(
        private readonly KioskRequisitionPrintService $printService,
    ) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->integer('receipt_job') > 0) {
            return redirect()->route('kiosk.receipts.index', [
                'receipt_job' => $request->integer('receipt_job'),
            ]);
        }

        /** @var User $kioskUser */
        $kioskUser = $request->attributes->get('kiosk_user');
        $receipt = $request->session()->get('kiosk_receipt');
        $preferredLabelRequestId = ($receipt['request_kind'] ?? null) === 'label'
            ? (int) ($receipt['request_id'] ?? 0)
            : null;
        $preferredMasterRequestId = ($receipt['request_kind'] ?? null) === 'master'
            ? (int) ($receipt['request_id'] ?? 0)
            : null;
        $preferredDummyRequestId = ($receipt['request_kind'] ?? null) === 'dummy'
            ? (int) ($receipt['request_id'] ?? 0)
            : null;
        $preferredMasterActionRequestId = ($receipt['request_kind'] ?? null) === 'master_action'
            ? (int) ($receipt['request_id'] ?? 0)
            : null;
        $printJob = $this->printService->pendingForUser(
            $kioskUser,
            $preferredLabelRequestId ?: null,
            $preferredMasterRequestId ?: null,
            $preferredDummyRequestId ?: null,
            $preferredMasterActionRequestId ?: null,
        );

        return view('kiosk.dashboard', [
            'requisitionPrintConfig' => $printJob
                ? $this->printService->receiptPayload($printJob, true)
                : null,
        ]);
    }
}
