<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\KioskRequisitionPrintJob;
use App\Models\User;
use App\Services\Kiosk\KioskRequisitionPrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KioskReceiptReprintController extends Controller
{
    public function __construct(
        private readonly KioskRequisitionPrintService $printService,
    ) {}

    public function reprint(Request $request, KioskRequisitionPrintJob $printJob): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'uuid']]);
        $this->authorizeReceipt($request, $printJob, $data['token']);

        return response()->json([
            'status' => 'ready',
            'zpl' => $this->printService->zplForReprint($printJob),
        ]);
    }

    public function confirm(Request $request, KioskRequisitionPrintJob $printJob): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'uuid'],
            'attempt_token' => ['required', 'uuid'],
            'printer_name' => ['nullable', 'string', 'max:255'],
        ]);
        $this->authorizeReceipt($request, $printJob, $data['token']);

        DB::table('kiosk_requisition_receipt_reprints')->insertOrIgnore([
            'kiosk_requisition_print_job_id' => $printJob->id,
            'attempt_token' => $data['attempt_token'],
            'printer_name' => $data['printer_name'] ?? null,
            'printed_at' => now(),
        ]);

        return response()->json([
            'status' => 'printed',
            'message' => 'Reimpresión del comprobante registrada.',
        ]);
    }

    private function authorizeReceipt(Request $request, KioskRequisitionPrintJob $printJob, string $token): void
    {
        /** @var User $user */
        $user = $request->attributes->get('kiosk_user');

        abort_unless(
            $printJob->requested_by_user_id === $user->id
                && hash_equals($printJob->token, $token)
                && $printJob->status === KioskRequisitionPrintJob::STATUS_PRINTED,
            404,
        );
    }
}
