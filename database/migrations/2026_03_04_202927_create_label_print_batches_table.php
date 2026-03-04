<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('label_print_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('label_request_id')
                ->constrained('label_requests')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Fase 1: este FK existe en tu diagrama, pero la tabla serial_weeks es Fase 2.
            // Lo dejamos nullable SIN foreign key por ahora para no bloquear las migraciones.
            $table->unsignedBigInteger('serial_week_id')->nullable();

            $table->foreignId('shift_id')
                ->constrained('shifts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('batch_type', ['print', 'reprint', 'rework'])->default('print');

            $table->string('reason', 255)->nullable(); // usado normalmente para rework/reprint

            $table->foreignId('printed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('printed_by_name', 120)->nullable();

            $table->timestamp('printed_at')->nullable();

            $table->timestamps();

            $table->index(['label_request_id', 'batch_type']);
            $table->index(['shift_id', 'printed_at']);
            $table->index('serial_week_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_batches');
    }
};