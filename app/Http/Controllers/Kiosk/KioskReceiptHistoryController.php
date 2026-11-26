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
        $userReceipts = KioskRequisitionPrintJob::query()
            ->where('requested_by_user_id', $kioskUser->id)
            ->with([
                'labelRequest:id,request_kind,job_number',
                'labelRequest.lpkLabelGroups:id,label_request_id',
                'labelRequest.lpkLabelGroups.items:id,label_request_lpk_label_group_id,job_number',
                'labelRequest.lpkShippingGroups:id,label_request_id',
                'labelRequest.lpkShippingGroups.items:id,label_request_lpk_shipping_group_id,job_number',
                'masterRequest:id,job_assembly,job_packaging',
                'masterActionRequest:id,master_request_id,action_type,proposed_rework',
                'masterActionRequest.masterRequest:id,job_assembly,job_packaging',
                'dummyRequest:id,job_number',
            ]);
        $receiptJobs = (clone $userReceipts)
            ->latest('id')
            ->paginate(10, ['*'], 'receipts_page')
            ->withQueryString();
        $selectedJobId = $request->integer('receipt_job');
        $printJob = $selectedJobId > 0
            ? ($receiptJobs->getCollection()->find($selectedJobId) ?? $userReceipts->findOrFail($selectedJobId))
            : null;
        $receiptHistory = $receiptJobs->getCollection()->map(function (KioskRequisitionPrintJob $job): array {
            $payload = $this->printService->clientPayload($job);

            return [
                'id' => $job->id,
                'name' => $payload['requestName'],
                'requestId' => $payload['requestId'],
                'jobDetails' => $this->jobDetails($job),
                'status' => $job->status,
                'createdAt' => $job->created_at?->timezone(config('kiosk.display_timezone'))->format('d/m/Y H:i'),
            ];
        });

        return view('kiosk.receipts.index', [
            'requisitionPrintConfig' => $printJob
                ? $this->printService->receiptPayload($printJob, false)
                : null,
            'selectedJobDetails' => $printJob ? $this->jobDetails($printJob) : null,
            'receiptHistory' => $receiptHistory,
            'receiptPages' => $receiptJobs,
        ]);
    }

    /** @return array<string, string> */
    private function jobDetails(KioskRequisitionPrintJob $receipt): array
    {
        $action = $receipt->masterActionRequest;
        $master = $action?->masterRequest ?? $receipt->masterRequest;
        if ($master) {
            $details = [
                'Job de ensamble' => $master->job_assembly,
                'Job de empaque' => $master->job_packaging,
            ];
            $proposal = $action?->proposed_rework;
            if (($proposal['job_assembly'] ?? null) !== $master->job_assembly) {
                $details['Job de ensamble propuesto'] = $proposal['job_assembly'] ?? null;
            }
            if (($proposal['job_packaging'] ?? null) !== $master->job_packaging) {
                $details['Job de empaque propuesto'] = $proposal['job_packaging'] ?? null;
            }

            return array_filter($details, 'filled');
        }

        $label = $receipt->labelRequest;
        $numbers = collect([$label?->job_number ?? $receipt->dummyRequest?->job_number]);
        if ($label?->isLpk()) {
            $numbers = $numbers
                ->concat($label->lpkLabelGroups->flatMap->items->pluck('job_number'))
                ->concat($label->lpkShippingGroups->flatMap->items->pluck('job_number'));
        }
        $numbers = $numbers->map(fn ($number) => trim((string) $number))
            ->filter('filled')
            ->uniqueStrict(fn (string $number) => strtoupper($number));

        return $numbers->isEmpty() ? [] : [
            $numbers->count() === 1 ? 'Job' : 'Jobs' => $numbers->implode(', '),
        ];
    }
}
