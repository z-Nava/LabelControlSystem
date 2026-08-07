<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->string('oracle_line', 40)->nullable()->after('destination');
            $table->string('subinventory', 20)->nullable()->after('oracle_line');
            $table->index('oracle_line');
            $table->index('subinventory');
        });

        $productionLines = DB::table('production_lines')->pluck('code', 'id');
        $oracleJobs = DB::table('oracle_jobs')
            ->get(['job_number', 'line'])
            ->keyBy(fn (object $job): string => $this->normalize($job->job_number));
        $mappings = DB::table('stock_locators')
            ->where('active', true)
            ->get(['oracle_line', 'subinventory', 'stock_locator'])
            ->keyBy(fn (object $mapping): string => $this->normalize($mapping->oracle_line));

        DB::table('master_requests')
            ->orderBy('id')
            ->chunkById(100, function ($masters) use ($productionLines, $oracleJobs, $mappings): void {
                foreach ($masters as $master) {
                    $oracleLine = $this->normalize($productionLines->get($master->line_id));

                    if ($oracleLine === '') {
                        $jobNumber = $this->normalize($master->job_assembly ?: $master->job_packaging);
                        $oracleLine = $this->normalize($oracleJobs->get($jobNumber)?->line);
                    }

                    $lineMapping = $mappings->get($oracleLine);
                    $currentLocal = $this->normalize($master->local);
                    $resolvedLocal = $currentLocal;

                    if (
                        in_array($master->request_type, ['batteries_assembly', 'assembly_packaging'], true)
                        && $this->normalize($lineMapping?->stock_locator) !== ''
                    ) {
                        $resolvedLocal = $this->normalize($lineMapping->stock_locator);
                    } elseif ($resolvedLocal === '') {
                        $resolvedLocal = $this->normalize($lineMapping?->stock_locator) ?: $oracleLine;
                    }

                    $localMapping = $mappings->get($resolvedLocal);
                    $subinventory = $this->normalize($lineMapping?->subinventory)
                        ?: $this->normalize($localMapping?->subinventory);

                    DB::table('master_requests')
                        ->where('id', $master->id)
                        ->update([
                            'oracle_line' => $oracleLine !== '' ? $oracleLine : null,
                            'subinventory' => $subinventory !== '' ? $subinventory : null,
                            'local' => $resolvedLocal !== '' ? $resolvedLocal : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->dropIndex(['oracle_line']);
            $table->dropIndex(['subinventory']);
            $table->dropColumn(['oracle_line', 'subinventory']);
        });
    }

    private function normalize(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }
};
