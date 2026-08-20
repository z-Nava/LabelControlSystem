<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosk_requisition_print_jobs', function (Blueprint $table) {
            $table->foreignId('dummy_request_id')
                ->nullable()
                ->unique()
                ->after('master_request_id')
                ->constrained('dummy_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('kiosk_requisition_print_jobs')
            ->whereNotNull('dummy_request_id')
            ->delete();

        Schema::table('kiosk_requisition_print_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dummy_request_id');
        });
    }
};
