<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\KioskRequisitionPrintJob;
use App\Models\User;
use App\Services\Kiosk\KioskRequisitionPrintService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KioskReceiptHistoryController extends Controller
{
    public function __construct(
        private readonly KioskRequisitionPrintService $printService,
    ) {}

    public function __invoke(Request $request): View
    {
        /** @var User $kioskUser */
        $kioskUser = $request->attributes->get('kiosk_user');
        $selectedJobId = $request->integer('receipt_job');
        $printJob = $selectedJobId > 0
            ? KioskRequisitionPrintJob::query()
                ->where('requested_by_user_id', $kioskUser->id)
                ->findOrFail($selectedJobId)
            : null;

        $receiptJobs = KioskRequisitionPrintJob::query()
            ->where('requested_by_user_id', $kioskUser->id)
            ->with(['labelRequest', 'masterRequest', 'masterActionRequest', 'dummyRequest'])
            ->latest('id')
            ->paginate(10, ['*'], 'receipts_page')
            ->withQueryString();
        $receiptHistory = $receiptJobs->getCollection()->map(function (KioskRequisitionPrintJob $job): array {
            $payload = $this->printService->clientPayload($job);

            return [
                'id' => $job->id,
                'name' => $payload['requestName'],
                'requestId' => $payload['requestId'],
                'status' => $job->status,
                'createdAt' => $job->created_at?->timezone(config('kiosk.display_timezone'))->format('d/m/Y H:i'),
            ];
        });

        return view('kiosk.receipts.index', [
            'requisitionPrintConfig' => $printJob
                ? $this->printService->receiptPayload($printJob, false)
                : null,
            'receiptHistory' => $receiptHistory,
            'receiptPages' => $receiptJobs,
        ]);
    }
}
