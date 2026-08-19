<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\LabelRequest;
use App\Models\User;
use App\Services\Kiosk\KioskRequisitionPrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KioskRequisitionPrintController extends Controller
{
    public function __construct(
        private readonly KioskRequisitionPrintService $printService,
    ) {}

    public function claim(Request $request, LabelRequest $label_request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'uuid'],
        ]);
        $result = $this->printService->claim(
            $label_request,
            $this->kioskUser($request),
            $data['token'],
        );

        if ($result['status'] === 'sending') {
            return response()->json([
                'status' => 'sending',
                'message' => 'La impresión ya está siendo procesada. Espera antes de reintentar.',
            ], 409);
        }

        if ($result['status'] === 'printed') {
            return response()->json([
                'status' => 'printed',
                'message' => 'Esta etiqueta ya fue confirmada como impresa.',
            ]);
        }

        return response()->json([
            'status' => 'claimed',
            'zpl' => $result['job']->zpl,
            'attempts' => $result['job']->attempts,
        ]);
    }

    public function confirm(Request $request, LabelRequest $label_request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'uuid'],
            'printer_name' => ['nullable', 'string', 'max:255'],
        ]);
        $printJob = $this->printService->confirm(
            $label_request,
            $this->kioskUser($request),
            $data['token'],
            $data['printer_name'] ?? null,
        );

        return response()->json([
            'status' => 'printed',
            'printed_at' => $printJob->printed_at?->toIso8601String(),
            'message' => 'Etiqueta de requisición enviada y confirmada.',
        ]);
    }

    public function fail(Request $request, LabelRequest $label_request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'uuid'],
            'error' => ['required', 'string', 'max:1000'],
            'printer_name' => ['nullable', 'string', 'max:255'],
        ]);
        $printJob = $this->printService->fail(
            $label_request,
            $this->kioskUser($request),
            $data['token'],
            $data['error'],
            $data['printer_name'] ?? null,
        );

        return response()->json([
            'status' => $printJob->status,
            'message' => 'La falla quedó registrada; la requisición permanece guardada.',
        ]);
    }

    private function kioskUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->attributes->get('kiosk_user');

        return $user;
    }
}
