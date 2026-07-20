<?php

namespace App\Services\Dummies;

use App\Models\DummyPrintBatch;
use App\Models\DummyQrTemplate;
use App\Models\DummyRequest;

class DummyPrintReadService
{
    public function __construct(
        private readonly DummyQRTemplateZplBuilder $zplBuilder,
    ) {}

    public function buildPrintCenterViewData(DummyRequest $dummyRequest, DummyPrintBatch $batch): array
    {
        $dummyRequest->loadMissing([
            'line:id,code,name',
            'shift:id,code,name',
        ]);

        $batch->load([
            'items.requestItem:id,dummy_request_id,consecutive,consecutive_10d,dummy_type,qr_payload',
        ]);

        $templates = DummyQrTemplate::query()
            ->whereIn('dummy_type', ['rmt', 'rw'])
            ->where('is_active', true)
            ->get()
            ->keyBy('dummy_type');

        $items = $batch->items->map(fn ($item) => [
            'consecutive' => (string) ($item->requestItem?->consecutive_10d ?? ''),
            'dummy_type' => strtolower((string) ($item->requestItem?->dummy_type ?? '')),
            'dummy_type_label' => strtoupper((string) ($item->requestItem?->dummy_type ?? '-')),
            'copies' => (int) $item->copies,
            'copies_formatted' => number_format((int) $item->copies),
            'qr_payload' => (string) ($item->requestItem?->qr_payload ?? ''),
        ])->values();

        $printCenter = [
            'request_id' => $dummyRequest->id,
            'batch_id' => $batch->id,
            'batch_type_label' => strtoupper((string) $batch->batch_type),
            'job_number' => (string) ($dummyRequest->job_number ?? ''),
            'fg_code' => (string) ($dummyRequest->fg_code ?? ''),
            'quantity' => (int) $batch->quantity,
            'quantity_formatted' => number_format((int) $batch->quantity),
            'line_code' => (string) ($dummyRequest->line?->code ?? '-'),
            'shift_code' => (string) ($dummyRequest->shift?->code ?? '-'),
            'detail_url' => route('dummy_requests.show', $dummyRequest),
            'items' => $items,
        ];

        return [
            'printCenter' => $printCenter,
            'printCenterConfig' => [
                'confirmUrl' => route('dummy_requests.print_batches.confirm', [
                    'dummy_request' => $dummyRequest,
                    'batch' => $batch,
                ]),
                'browserPrintUrl' => asset('vendor/zebra/BrowserPrint-3.1.250.min.js'),
                'alreadyPrinted' => $batch->printed_at !== null,
                'jobNumber' => $printCenter['job_number'],
                'fgCode' => $printCenter['fg_code'],
                'items' => $items->map(fn (array $item) => [
                    'dummyType' => $item['dummy_type'],
                    'copies' => $item['copies'],
                    'consecutive' => $item['consecutive'],
                    'qrPayload' => $item['qr_payload'],
                ])->all(),
                'templatesByType' => [
                    'rmt' => $this->buildCurrentTemplateZpl($templates->get('rmt')),
                    'rw' => $this->buildCurrentTemplateZpl($templates->get('rw')),
                ],
            ],
        ];
    }

    private function buildCurrentTemplateZpl(?DummyQrTemplate $template): ?string
    {
        if (! $template) {
            return null;
        }

        return $this->zplBuilder->build($template->toArray());
    }
}
