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
        Schema::create('dummy_print_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dummy_request_id')
                ->constrained('dummy_requests')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('shift_id')
                ->constrained('shifts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('batch_type', ['print', 'reprint'])->default('print');
            $table->string('reason', 255)->nullable();

            $table->foreignId('printed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('printed_by_name', 120)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamp('printed_at')->nullable();

            $table->timestamps();

            $table->index(['dummy_request_id', 'batch_type']);
            $table->index(['shift_id', 'printed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dummy_print_batches');
    }
};
