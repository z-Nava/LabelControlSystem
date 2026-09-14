<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep historical kiosk weeks; new requests use Label Room's control_week.
        Schema::table('label_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('week')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('label_requests')->whereNull('week')->exists()) {
            throw new RuntimeException('Completa las semanas históricas pendientes antes de revertir esta migración.');
        }

        Schema::table('label_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('week')->nullable(false)->change();
        });
    }
};
