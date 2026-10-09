<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('label_request_lpk_label_items', function (Blueprint $table) {
            $table->string('po_number', 80)->nullable();
        });

        DB::table('label_request_lpk_label_items')->select('id', 'job_number')
            ->orderBy('id')->chunkById(500, function ($items): void {
                $poByJob = DB::table('oracle_jobs')
                    ->whereIn('job_number', $items->pluck('job_number')->unique()->all())
                    ->pluck('ttl_cust_po', 'job_number');

                foreach ($items as $item) {
                    $po = strtoupper(trim((string) ($poByJob[$item->job_number] ?? '')));
                    if ($po !== '') {
                        DB::table('label_request_lpk_label_items')->where('id', $item->id)
                            ->update(['po_number' => $po]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('label_request_lpk_label_items', function (Blueprint $table) {
            $table->dropColumn('po_number');
        });
    }
};
