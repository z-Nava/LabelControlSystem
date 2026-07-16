<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('label_print_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('label_print_batch_id')
                ->constrained('label_print_batches')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->enum('label_type', ['serial', 'rating']);
            $table->unsignedInteger('sequence');
            $table->unsignedInteger('unit_count')->default(0);
            $table->unsignedInteger('label_count')->default(0);
            $table->enum('status', ['pending', 'sent', 'confirmed', 'failed'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('last_error', 255)->nullable();

            $table->timestamps();

            $table->unique(['label_print_batch_id', 'label_type', 'sequence'], 'uq_label_print_blocks_batch_type_seq');
            $table->index(['label_print_batch_id', 'status']);
            $table->index(['label_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_blocks');
    }
};
