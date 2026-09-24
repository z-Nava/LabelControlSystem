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
        $area = $request->query('area');
        $lineId = $request->query('line_id');

        return view('dashboards.master_metrics', [
            'masterPrintMetrics' => $metricsService->forLastDays(
                groupBy: $request->query('metrics_group') === 'area' ? 'area' : 'line',
                area: is_string($area) ? $area : null,
                lineId: is_string($lineId) && ctype_digit($lineId) ? (int) $lineId : null,
            ),
        ]);
    }
}
