<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('serial_ranges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('serial_week_id')
                ->constrained('serial_weeks')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // rangos consumidos (ej 1..300)
            $table->unsignedInteger('range_start');
            $table->unsignedInteger('range_end');
            $table->unsignedInteger('quantity');

            $table->foreignId('label_request_id')
                ->constrained('label_requests')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            // Índices
            $table->index(['serial_week_id', 'range_start', 'range_end']);
            $table->index('label_request_id');

            // Evitar duplicar exactamente el mismo rango dentro de una misma semana
            $table->unique(['serial_week_id', 'range_start', 'range_end'], 'uq_serial_ranges_week_start_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_ranges');
    }
};