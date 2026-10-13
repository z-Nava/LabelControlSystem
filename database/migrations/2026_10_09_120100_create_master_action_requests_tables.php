<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_action_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_request_id')->constrained('master_requests')->restrictOnDelete();
            $table->string('action_type', 20);
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('copies')->nullable();
            $table->unsignedSmallInteger('additional_folios_count')->default(0);
            $table->text('reason');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requested_by_name', 120);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewed_by_name', 120)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('resulting_master_request_id')->nullable()->constrained('master_requests')->nullOnDelete();
            $table->foreignId('resulting_print_batch_id')->nullable()->constrained('master_print_batches')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['master_request_id', 'created_at']);
        });

        Schema::create('master_action_request_folios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_action_request_id')->constrained('master_action_requests')->cascadeOnDelete();
            $table->foreignId('master_request_folio_id')->constrained('master_request_folios')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['master_action_request_id', 'master_request_folio_id'], 'master_action_folios_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_action_request_folios');
        Schema::dropIfExists('master_action_requests');
    }
};
