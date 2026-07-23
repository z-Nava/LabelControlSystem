<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\PendingRequestCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingRequestCountController extends Controller
{
    public function __invoke(Request $request, PendingRequestCountService $countService): JsonResponse
    {
        return response()
            ->json([
                'counts' => $countService->for($request->user()),
            ])
            ->header('Cache-Control', 'no-store, private');
    }
}
