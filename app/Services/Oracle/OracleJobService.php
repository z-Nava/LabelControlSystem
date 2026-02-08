<?php

namespace App\Services\Oracle;

use App\Imports\OracleJobsImport;
use App\Models\OracleJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class OracleJobService
{
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $q = $filters['q'] ?? null;
        $line = $filters['line'] ?? null;
        $status = $filters['job_status'] ?? null;

        return OracleJob::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('job_number', 'like', "%{$q}%")
                       ->orWhere('assembly', 'like', "%{$q}%")
                       ->orWhere('part_description', 'like', "%{$q}%")
                       ->orWhere('ttl_cust_po', 'like', "%{$q}%");
                });
            })
            ->when($line, fn($query) => $query->where('line', $line))
            ->when($status, fn($query) => $query->where('job_status', $status))
            ->orderByDesc('last_update_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function importFromExcel(UploadedFile $file): array
    {
        // Leemos el excel con heading row
        $rows = Excel::toArray(new OracleJobsImport(), $file)[0] ?? [];

        $sourceName = $file->getClientOriginalName();
        $now = now();

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $sourceName, $now, &$inserted, &$updated, &$skipped) {
            foreach ($rows as $row) {
                $data = OracleJobsImport::normalizeRow($row);

                if (empty($data['job_number'])) {
                    $skipped++;
                    continue;
                }

                $data['source_file_name'] = $sourceName;
                $data['imported_at'] = $now;

                $existing = OracleJob::where('job_number', $data['job_number'])->first();

                if (!$existing) {
                    OracleJob::create($data);
                    $inserted++;
                } else {
                    $existing->update($data);
                    $updated++;
                }
            }
        });

        return compact('inserted', 'updated', 'skipped');
    }
}
