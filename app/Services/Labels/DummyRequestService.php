<?php

namespace App\Services\Labels;

use App\Models\DummyRequest;
use App\Models\DummyRequestItem;
use App\Services\Oracle\OracleJobLookupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DummyRequestService
{
    private const STATUS_REQUESTED = 'requested';

    public function __construct(
        private readonly OracleJobLookupService $oracleJobLookup,
    ) {}

    public function create(array $data): DummyRequest
    {
        return DB::transaction(function () use ($data): DummyRequest {
            $jobNumber = (string) $data['job_number'];
            $job = $this->oracleJobLookup->findByJobNumber($jobNumber);

            if (!$job) {
                throw ValidationException::withMessages([
                    'job_number' => 'No se encontró el Job en Oracle para generar dummys QR.',
                ]);
            }

            $fgCode = strtoupper(trim((string) $job->assembly));

            if ($fgCode === '') {
                throw ValidationException::withMessages([
                    'job_number' => 'El Job no tiene assembly (FG) disponible en Oracle.',
                ]);
            }

            $quantityRequested = (int) $data['quantity_requested'];
            $requestType = (string) $data['request_type'];
            $dummyType = $requestType === 'rework' ? 'rw' : 'rmt';
            $qrPrefix = $dummyType === 'rw' ? 'RW' : 'DM';

            $currentMaxConsecutive = (int) DummyRequestItem::query()
                ->where('job_number', $jobNumber)
                ->lockForUpdate()
                ->max('consecutive');

            $rangeFrom = $currentMaxConsecutive + 1;
            $rangeTo = $rangeFrom + $quantityRequested - 1;

            $request = DummyRequest::query()->create([
                'request_date' => $data['request_date'],
                'week' => (int) $data['week'],
                'line_id' => (int) $data['line_id'],
                'shift_id' => (int) $data['shift_id'],
                'leader_name' => $data['leader_name'],
                'requested_by_name' => $data['requested_by_name'],
                'requested_by_user_id' => $data['requested_by_user_id'] ?? null,
                'job_number' => $jobNumber,
                'fg_code' => $fgCode,
                'quantity_requested' => $quantityRequested,
                'range_from' => $rangeFrom,
                'range_to' => $rangeTo,
                'request_type' => $requestType,
                'status' => self::STATUS_REQUESTED,
                'notes' => $data['notes'] ?? null,
            ]);

            $itemsPayload = [];

            for ($consecutive = $rangeFrom; $consecutive <= $rangeTo; $consecutive++) {
                $consecutive10d = str_pad((string) $consecutive, 10, '0', STR_PAD_LEFT);

                $itemsPayload[] = [
                    'dummy_request_id' => $request->id,
                    'job_number' => $jobNumber,
                    'fg_code' => $fgCode,
                    'consecutive' => $consecutive,
                    'consecutive_10d' => $consecutive10d,
                    'dummy_type' => $dummyType,
                    'qr_payload' => "^{$qrPrefix}^{$fgCode}^{$jobNumber}^{$consecutive10d}^",
                    'print_count' => 0,
                    'last_printed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DummyRequestItem::query()->insert($itemsPayload);

            return $request->load(['line', 'shift']);
        });
    }

    public function lookupOracleJob(string $jobNumber): array
    {
        $payload = $this->oracleJobLookup->buildLookupPayload($jobNumber);

        if ($payload['found'] ?? false) {
            $payload['fg_code'] = strtoupper(trim((string) ($payload['assembly'] ?? '')));
        }

        return $payload;
    }
}
