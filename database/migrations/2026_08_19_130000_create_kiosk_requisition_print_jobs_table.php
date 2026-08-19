<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosk_requisition_print_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('label_request_id')
                ->unique()
                ->constrained('label_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('printer_name', 255)->nullable();
            $table->longText('zpl');
            $table->text('last_error')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['requested_by_user_id', 'status'], 'idx_kiosk_req_print_user_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_requisition_print_jobs');
    }
};
