<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\MasterPrintMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterMetricsController extends Controller
{
    public function __invoke(Request $request, MasterPrintMetricsService $metricsService): View
    {
        return view('dashboards.master_metrics', [
            'masterPrintMetrics' => $metricsService->forLastDays(
                groupBy: $request->query('metrics_group') === 'area' ? 'area' : 'line',
            ),
        ]);
    }
}
