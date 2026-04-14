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
        Schema::create('dummy_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dummy_request_id')
                ->constrained('dummy_requests')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('job_number', 40);
            $table->string('fg_code', 80);

            $table->unsignedBigInteger('consecutive');
            $table->string('consecutive_10d', 10);

            $table->enum('dummy_type', ['rmt', 'rw'])->default('rmt');
            $table->string('qr_payload', 255);

            $table->unsignedInteger('print_count')->default(0);
            $table->timestamp('last_printed_at')->nullable();

            $table->timestamps();

            $table->unique(['job_number', 'consecutive']);
            $table->unique(['job_number', 'consecutive_10d']);
            $table->index(['dummy_request_id', 'dummy_type']);
            $table->index('fg_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dummy_request_items');
    }
};
