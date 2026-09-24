<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MasterPrintMetricsService
{
    /**
     * @return array{since: Carbon, group_by: string, reprint_requests: int, rework_requests: int, lines_with_reprints: int, lines_with_reworks: int, reprint_lines: array<int, array{code: string, area: string, requests: int}>, rework_lines: array<int, array{code: string, area: string, requests: int}>}
     */
    public function forLastDays(int $days = 90, string $groupBy = 'line'): array
    {
        $since = now()->startOfDay()->subDays($days);
        $groupBy = $groupBy === 'area' ? 'area' : 'line';

        // A reprint batch is created when the request is made. Printing may still be pending.
        $reprintCounts = DB::table('master_print_batches as batches')
            ->join('master_requests as requests', 'requests.id', '=', 'batches.master_request_id')
            ->where('batches.batch_type', 'reprint')
            ->where('batches.created_at', '>=', $since)
            ->select('requests.line_id')
            ->selectRaw('COUNT(*) as request_count')
            ->groupBy('requests.line_id')
            ->get();

        // A revision is the rework request. Its later print batch must not count again.
        $revisions = DB::table('master_requests')
            ->select('line_id')
            ->whereNotNull('parent_master_request_id')
            ->where('created_at', '>=', $since);

        // The older direct rework action creates a batch on the original request.
        $directReworkBatches = DB::table('master_print_batches as batches')
            ->join('master_requests as requests', 'requests.id', '=', 'batches.master_request_id')
            ->select('requests.line_id')
            ->where('batches.batch_type', 'rework')
            ->whereNull('requests.parent_master_request_id')
            ->where('batches.created_at', '>=', $since);

        $reworkCounts = DB::query()
            ->fromSub($revisions->unionAll($directReworkBatches), 'events')
            ->select('line_id')
            ->selectRaw('COUNT(*) as request_count')
            ->groupBy('line_id')
            ->get();

        $catalog = DB::table('production_lines')
            ->select('id', 'code', 'line_type')
            ->get()
            ->keyBy('id');
        $reprintLines = $this->rankedGroups($reprintCounts, $catalog, $groupBy);
        $reworkLines = $this->rankedGroups($reworkCounts, $catalog, $groupBy);

        return [
            'since' => $since,
            'group_by' => $groupBy,
            'reprint_requests' => (int) $reprintLines->sum('requests'),
            'rework_requests' => (int) $reworkLines->sum('requests'),
            'lines_with_reprints' => $reprintLines->count(),
            'lines_with_reworks' => $reworkLines->count(),
            'reprint_lines' => $reprintLines->take(10)->all(),
            'rework_lines' => $reworkLines->take(10)->all(),
        ];
    }

    /**
     * @return Collection<int, array{code: string, area: string, requests: int}>
     */
    private function rankedGroups(Collection $counts, Collection $catalog, string $groupBy): Collection
    {
        return $counts
            ->map(function ($row) use ($catalog): array {
                $line = $catalog->get($row->line_id);

                return [
                    'code' => (string) ($line->code ?? 'SIN LÍNEA'),
                    'area' => (string) ($line->line_type ?? 'SIN ÁREA'),
                    'requests' => (int) $row->request_count,
                ];
            })
            ->groupBy(fn (array $row) => $groupBy === 'area' ? $row['area'] : $row['code'])
            ->map(fn (Collection $rows, string $label): array => [
                'code' => $label,
                'area' => $groupBy === 'area' ? '' : $rows->first()['area'],
                'requests' => (int) $rows->sum('requests'),
            ])
            ->sortBy([
                ['requests', 'desc'],
                ['code', 'asc'],
            ])
            ->values();
    }
}
