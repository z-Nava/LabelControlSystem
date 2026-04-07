<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('label_requests', function (Blueprint $table) {
            $table->id();

            $table->date('request_date');
            $table->unsignedTinyInteger('week'); // 1-53 (calculada pero editable)

            $table->foreignId('line_id')
                ->constrained('production_lines')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('shift_id')
                ->constrained('shifts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('leader_name', 120);
            $table->string('requested_by_name', 120);

            $table->foreignId('requested_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Ojo: en tu diagrama dice label_part_number (que es el NP de la etiqueta)
            $table->string('label_part_number', 80);
            $table->string('serial_standard', 10)->default('UL'); // UL | EMEA

            $table->string('po_number', 80)->nullable();
            $table->string('destination', 80)->nullable();

            $table->string('model', 80)->nullable();
            $table->string('job_number', 40)->nullable();

            $table->unsignedInteger('quantity_requested');

            $table->boolean('include_serial')->default(false);
            $table->boolean('include_rating')->default(false);

            $table->enum('status', ['requested', 'in_progress', 'completed', 'cancelled'])
                ->default('requested');

            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices prácticos
            $table->index(['request_date', 'week']);
            $table->index(['line_id', 'shift_id']);
            $table->index('label_part_number');
            $table->index('serial_standard');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_requests');
    }
};
