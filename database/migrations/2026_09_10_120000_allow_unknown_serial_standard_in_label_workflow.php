<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['label_requests', 'serial_weeks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('serial_standard', 10)->nullable()->default(null)->change();
            });
        }
    }

    public function down(): void
    {
        // Restoring NOT NULL requires a verified standard for every existing record.
        foreach (['label_requests', 'serial_weeks'] as $tableName) {
            if (DB::table($tableName)->whereNull('serial_standard')->exists()) {
                throw new RuntimeException('Completa los estándares pendientes antes de revertir esta migración.');
            }
        }
        foreach (['label_requests', 'serial_weeks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('serial_standard', 10)->nullable(false)->default('UL')->change();
            });
        }
    }
};
