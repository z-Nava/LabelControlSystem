<?php

namespace App\Services\Kiosk;

use App\Models\KioskRequisitionPrintJob;
use App\Models\LabelRequest;
use App\Models\MasterRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KioskRequisitionPrintService
{
    public function __construct(
        private readonly KioskRequisitionLabelZplBuilder $labelZplBuilder,
        private readonly KioskMasterRequisitionLabelZplBuilder $masterZplBuilder,
    ) {}

    public function prepare(LabelRequest $labelRequest, User $user): KioskRequisitionPrintJob
    {
        $existingJob = KioskRequisitionPrintJob::query()
            ->where('label_request_id', $labelRequest->id)
            ->first();

        if ($existingJob) {
            return $existingJob;
        }

        $labelRequest->loadMissing(['line', 'shift', 'serials', 'ratings', 'shippingItems']);
        $dpi = (int) config('kiosk.requisition_label.dpi', 203);

        return KioskRequisitionPrintJob::query()->create([
            'token' => (string) Str::uuid(),
            'label_request_id' => $labelRequest->id,
            'requested_by_user_id' => $user->id,
            'status' => KioskRequisitionPrintJob::STATUS_PENDING,
            'attempts' => 0,
            'zpl' => $this->labelZplBuilder->build($labelRequest, $dpi),
        ]);
    }

    public function prepareMaster(MasterRequest $masterRequest, User $user): KioskRequisitionPrintJob
    {
        $existingJob = KioskRequisitionPrintJob::query()
            ->where('master_request_id', $masterRequest->id)
            ->first();

        if ($existingJob) {
            return $existingJob;
        }

        $masterRequest->loadMissing(['line', 'shift', 'folios']);
        $dpi = (int) config('kiosk.requisition_label.dpi', 203);

        return KioskRequisitionPrintJob::query()->create([
            'token' => (string) Str::uuid(),
            'master_request_id' => $masterRequest->id,
            'requested_by_user_id' => $user->id,
            'status' => KioskRequisitionPrintJob::STATUS_PENDING,
            'attempts' => 0,
            'zpl' => $this->masterZplBuilder->build($masterRequest, $dpi),
        ]);
    }

    public function pendingForUser(
        User $user,
        ?int $preferredLabelRequestId = null,
        ?int $preferredMasterRequestId = null,
    ): ?KioskRequisitionPrintJob {
        $query = KioskRequisitionPrintJob::query()
            ->where('requested_by_user_id', $user->id)
            ->where('status', '<>', KioskRequisitionPrintJob::STATUS_PRINTED)
            ->where(function ($query) {
                $query
                    ->whereHas('labelRequest', fn ($query) => $query->where('status', '<>', LabelRequest::STATUS_CANCELLED))
                    ->orWhereHas('masterRequest', fn ($query) => $query->where('status', '<>', MasterRequest::STATUS_CANCELLED));
            });

        if ($preferredLabelRequestId !== null) {
            $preferredJob = (clone $query)
                ->where('label_request_id', $preferredLabelRequestId)
                ->first();

            if ($preferredJob) {
                return $preferredJob;
            }
        }

        if ($preferredMasterRequestId !== null) {
            $preferredJob = (clone $query)
                ->where('master_request_id', $preferredMasterRequestId)
                ->first();

            if ($preferredJob) {
                return $preferredJob;
            }
        }

        return $query->oldest('created_at')->oldest('id')->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function clientPayload(KioskRequisitionPrintJob $printJob): array
    {
        $isMaster = $printJob->master_request_id !== null;
        $requestId = $isMaster
            ? $printJob->master_request_id
            : $printJob->label_request_id;
        $routePrefix = $isMaster
            ? 'kiosk.master_requests.requisition_label'
            : 'kiosk.label_requests.requisition_label';
        $requestName = match (true) {
            $isMaster => 'Requisición Master',
            $printJob->labelRequest?->isLpk() => 'Requisición de etiquetas LPK',
            default => 'Requisición de etiquetas',
        };

        return [
            'requestId' => $requestId,
            'requestName' => $requestName,
            'token' => $printJob->token,
            'status' => $printJob->status,
            'attempts' => $printJob->attempts,
            'claimUrl' => route("{$routePrefix}.claim", $requestId),
            'confirmUrl' => route("{$routePrefix}.confirm", $requestId),
            'failUrl' => route("{$routePrefix}.fail", $requestId),
            'browserPrintUrl' => asset('vendor/zebra/BrowserPrint-3.1.250.min.js'),
            'defaultPrinterName' => config('kiosk.requisition_label.default_printer_name'),
            'labelSize' => sprintf(
                '%d × %d cm',
                ((int) config('kiosk.requisition_label.width_mm', 100)) / 10,
                ((int) config('kiosk.requisition_label.height_mm', 100)) / 10,
            ),
        ];
    }

    /**
     * @return array{status: string, job: KioskRequisitionPrintJob}
     */
    public function claim(LabelRequest $labelRequest, User $user, string $token): array
    {
        return $this->claimRequest($labelRequest, 'label_request_id', $user, $token);
    }

    /**
     * @return array{status: string, job: KioskRequisitionPrintJob}
     */
    public function claimMaster(MasterRequest $masterRequest, User $user, string $token): array
    {
        return $this->claimRequest($masterRequest, 'master_request_id', $user, $token);
    }

    /**
     * @return array{status: string, job: KioskRequisitionPrintJob}
     */
    private function claimRequest(
        LabelRequest|MasterRequest $request,
        string $foreignKey,
        User $user,
        string $token,
    ): array {
        return DB::transaction(function () use ($request, $foreignKey, $user, $token): array {
            $printJob = $this->authorizedJobQuery($request, $foreignKey, $user, $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($printJob->status === KioskRequisitionPrintJob::STATUS_PRINTED) {
                return ['status' => 'printed', 'job' => $printJob];
            }

            $timeoutSeconds = max(15, (int) config('kiosk.requisition_label.claim_timeout_seconds', 45));
            $claimIsActive = $printJob->status === KioskRequisitionPrintJob::STATUS_SENDING
                && $printJob->dispatched_at?->isAfter(now()->subSeconds($timeoutSeconds));

            if ($claimIsActive) {
                return ['status' => 'sending', 'job' => $printJob];
            }

            $printJob->update([
                'status' => KioskRequisitionPrintJob::STATUS_SENDING,
                'attempts' => $printJob->attempts + 1,
                'last_error' => null,
                'dispatched_at' => now(),
            ]);

            return ['status' => 'claimed', 'job' => $printJob->refresh()];
        });
    }

    public function confirm(
        LabelRequest $labelRequest,
        User $user,
        string $token,
        ?string $printerName,
    ): KioskRequisitionPrintJob {
        return $this->confirmRequest($labelRequest, 'label_request_id', $user, $token, $printerName);
    }

    public function confirmMaster(
        MasterRequest $masterRequest,
        User $user,
        string $token,
        ?string $printerName,
    ): KioskRequisitionPrintJob {
        return $this->confirmRequest($masterRequest, 'master_request_id', $user, $token, $printerName);
    }

    private function confirmRequest(
        LabelRequest|MasterRequest $request,
        string $foreignKey,
        User $user,
        string $token,
        ?string $printerName,
    ): KioskRequisitionPrintJob {
        return DB::transaction(function () use ($request, $foreignKey, $user, $token, $printerName): KioskRequisitionPrintJob {
            $printJob = $this->authorizedJobQuery($request, $foreignKey, $user, $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($printJob->status !== KioskRequisitionPrintJob::STATUS_PRINTED) {
                $printJob->update([
                    'status' => KioskRequisitionPrintJob::STATUS_PRINTED,
                    'printer_name' => $printerName ?: $printJob->printer_name,
                    'last_error' => null,
                    'printed_at' => now(),
                ]);
            }

            return $printJob->refresh();
        });
    }

    public function fail(
        LabelRequest $labelRequest,
        User $user,
        string $token,
        string $error,
        ?string $printerName,
    ): KioskRequisitionPrintJob {
        return $this->failRequest($labelRequest, 'label_request_id', $user, $token, $error, $printerName);
    }

    public function failMaster(
        MasterRequest $masterRequest,
        User $user,
        string $token,
        string $error,
        ?string $printerName,
    ): KioskRequisitionPrintJob {
        return $this->failRequest($masterRequest, 'master_request_id', $user, $token, $error, $printerName);
    }

    private function failRequest(
        LabelRequest|MasterRequest $request,
        string $foreignKey,
        User $user,
        string $token,
        string $error,
        ?string $printerName,
    ): KioskRequisitionPrintJob {
        return DB::transaction(function () use ($request, $foreignKey, $user, $token, $error, $printerName): KioskRequisitionPrintJob {
            $printJob = $this->authorizedJobQuery($request, $foreignKey, $user, $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($printJob->status !== KioskRequisitionPrintJob::STATUS_PRINTED) {
                $printJob->update([
                    'status' => KioskRequisitionPrintJob::STATUS_FAILED,
                    'printer_name' => $printerName ?: $printJob->printer_name,
                    'last_error' => Str::limit(trim($error), 1000, ''),
                ]);
            }

            return $printJob->refresh();
        });
    }

    private function authorizedJobQuery(
        LabelRequest|MasterRequest $request,
        string $foreignKey,
        User $user,
        string $token,
    ) {
        return KioskRequisitionPrintJob::query()
            ->where($foreignKey, $request->id)
            ->where('requested_by_user_id', $user->id)
            ->where('token', $token);
    }
}
