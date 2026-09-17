<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('label_requests', function (Blueprint $table) {
            $table->json('catalog_context')->nullable();
        });
        Schema::table('label_request_serials', function (Blueprint $table) {
            $table->unique(['label_request_id', 'position'], 'uq_label_request_serial_position');
        });
        Schema::table('label_request_serials', function (Blueprint $table) {
            $table->dropUnique('uq_label_request_serial_part');
        });
    }

    public function down(): void
    {
        if (DB::table('label_request_serials')->select('label_request_id', 'part_number')
            ->groupBy('label_request_id', 'part_number')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede restaurar la restricción anterior: existen combos con el mismo NP Serial y distintos Ratings.');
        }
        Schema::table('label_request_serials', function (Blueprint $table) {
            $table->unique(['label_request_id', 'part_number'], 'uq_label_request_serial_part');
        });
        Schema::table('label_request_serials', function (Blueprint $table) {
            $table->dropUnique('uq_label_request_serial_position');
        });
        Schema::table('label_requests', function (Blueprint $table) {
            $table->dropColumn('catalog_context');
        });
    }
};
