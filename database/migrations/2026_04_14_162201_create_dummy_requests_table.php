<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dummy_requests', function (Blueprint $table) {
            $table->id();

            $table->date('request_date');
            $table->unsignedTinyInteger('week');

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

            $table->string('job_number', 40);
            $table->string('fg_code', 80);

            $table->unsignedInteger('quantity_requested');
            $table->unsignedBigInteger('range_from')->nullable();
            $table->unsignedBigInteger('range_to')->nullable();

            $table->enum('request_type', ['first_time', 'rework'])->default('first_time');

            $table->enum('status', ['requested', 'in_progress', 'completed'])
                ->default('requested');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['request_date', 'week']);
            $table->index(['job_number', 'status']);
            $table->index('request_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dummy_requests');
    }
};
