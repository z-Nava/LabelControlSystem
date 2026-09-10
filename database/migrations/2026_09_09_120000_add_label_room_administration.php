<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_model_mappings', function (Blueprint $table) {
            $table->string('folio_family', 80)->nullable();
        });
        Schema::table('serial_weeks', function (Blueprint $table) {
            // NULL preserves historical rows without guessing their family.
            $table->string('folio_family', 80)->nullable();
            $table->unsignedInteger('opening_serial_number')->default(0);
            $table->text('opening_notes')->nullable();
            $table->foreignId('initialized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dropUnique('uq_serial_weeks_pn_std_year_week');
            $table->unique(['label_part_number', 'folio_family', 'year', 'week'], 'uq_serial_weeks_family_period');
        });
        Schema::table('label_requests', function (Blueprint $table) {
            $table->string('folio_mode', 30)->default('new');
            $table->string('job_status', 2)->default('P');
            $table->unsignedSmallInteger('control_year')->nullable();
            $table->unsignedTinyInteger('control_week')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('originals_received_at')->nullable();
            $table->timestamp('physical_signed_at')->nullable();
            $table->foreignId('physical_signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('serial_ranges', function (Blueprint $table) {
            $table->unsignedInteger('production_quantity')->nullable();
            $table->unsignedSmallInteger('evidence_quantity')->default(0);
            $table->unsignedInteger('evidence_folio')->nullable();
            $table->string('job_number', 40)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('status', 20)->default('reserved');
            $table->dropForeign(['serial_week_id']);
            $table->foreign('serial_week_id')->references('id')->on('serial_weeks')->restrictOnDelete()->cascadeOnUpdate();
            $table->dropForeign(['label_request_id']);
            $table->foreign('label_request_id')->references('id')->on('label_requests')->restrictOnDelete()->cascadeOnUpdate();
        });
        Schema::create('label_work_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_id')->constrained('label_requests')->restrictOnDelete();
            $table->string('source_key', 100);
            $table->string('rating_part_number', 80)->nullable();
            $table->string('folio_family', 80)->nullable();
            $table->unsignedSmallInteger('control_year')->nullable();
            $table->unsignedTinyInteger('control_week')->nullable();
            $table->string('original_reference', 255)->nullable();
            $table->string('label_type', 15);
            $table->string('part_number', 80);
            $table->string('model', 80)->nullable();
            $table->string('job_number', 40)->nullable();
            $table->json('jobs');
            $table->string('po_number', 80)->nullable();
            $table->string('destination', 80)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedSmallInteger('evidence_quantity')->default(1);
            $table->foreignId('serial_range_id')->nullable()->constrained('serial_ranges')->restrictOnDelete();
            $table->unsignedInteger('folio_start')->nullable();
            $table->unsignedInteger('folio_end')->nullable();
            $table->unsignedInteger('evidence_folio')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('printed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('printed_by_name', 120)->nullable();
            $table->foreignId('printed_shift_id')->nullable()->constrained('shifts')->restrictOnDelete();
            $table->date('work_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['label_request_id', 'source_key']);
            $table->index(['assigned_to_user_id', 'status']);
        });
        Schema::create('label_job_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_id')->constrained('label_requests')->restrictOnDelete();
            $table->string('job_number', 40);
            $table->string('model', 80)->default('');
            $table->string('po_number', 80)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('shipping_quantity')->nullable();
            $table->json('shared_shipping')->nullable();
            $table->date('work_date');
            $table->foreignId('line_id')->constrained('production_lines')->restrictOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['label_request_id', 'job_number', 'model'], 'uq_label_job_entry_request_job_model');
            $table->index(['work_date', 'shift_id']);
        });
        Schema::create('label_administration_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_id')->nullable()->constrained('label_requests')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->json('details');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_administration_events');
        Schema::dropIfExists('label_job_entries');
        Schema::dropIfExists('label_work_tasks');
        Schema::table('serial_ranges', function (Blueprint $table) {
            $table->dropColumn(['production_quantity', 'evidence_quantity', 'evidence_folio', 'job_number', 'model', 'status']);
            $table->dropForeign(['serial_week_id']);
            $table->foreign('serial_week_id')->references('id')->on('serial_weeks')->cascadeOnDelete()->cascadeOnUpdate();
            $table->dropForeign(['label_request_id']);
            $table->foreign('label_request_id')->references('id')->on('label_requests')->cascadeOnDelete()->cascadeOnUpdate();
        });
        Schema::table('label_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('released_by_user_id');
            $table->dropConstrainedForeignId('physical_signed_by_user_id');
            $table->dropColumn(['folio_mode', 'job_status', 'control_year', 'control_week', 'review_notes', 'released_at', 'originals_received_at', 'physical_signed_at']);
        });
        Schema::table('serial_weeks', function (Blueprint $table) {
            $table->dropUnique('uq_serial_weeks_family_period');
            $table->dropConstrainedForeignId('initialized_by_user_id');
            $table->dropColumn(['folio_family', 'opening_serial_number', 'opening_notes']);
            $table->unique(['label_part_number', 'serial_standard', 'year', 'week'], 'uq_serial_weeks_pn_std_year_week');
        });
        Schema::table('master_model_mappings', fn (Blueprint $table) => $table->dropColumn('folio_family'));
    }
};
