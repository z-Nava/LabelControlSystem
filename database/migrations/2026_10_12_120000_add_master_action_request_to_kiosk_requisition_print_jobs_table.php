<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosk_requisition_print_jobs', function (Blueprint $table) {
            $table->foreignId('master_action_request_id')
                ->nullable()
                ->unique()
                ->after('master_request_id')
                ->constrained('master_action_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kiosk_requisition_print_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('master_action_request_id');
        });
    }
};
