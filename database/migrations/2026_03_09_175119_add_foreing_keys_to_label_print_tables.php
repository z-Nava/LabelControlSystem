<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('label_print_batches', function (Blueprint $table) {
            // si ya existe como unsignedBigInteger, solo agregamos FK
            $table->foreign('serial_week_id')
                ->references('id')->on('serial_weeks')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('label_print_batch_items', function (Blueprint $table) {
            $table->foreign('serial_unit_id')
                ->references('id')->on('serial_units')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('label_print_batches', function (Blueprint $table) {
            $table->dropForeign(['serial_week_id']);
        });

        Schema::table('label_print_batch_items', function (Blueprint $table) {
            $table->dropForeign(['serial_unit_id']);
        });
    }
};