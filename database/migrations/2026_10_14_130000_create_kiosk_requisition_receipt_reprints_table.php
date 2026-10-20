<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kiosk_requisition_receipt_reprints')) {
            Schema::create('kiosk_requisition_receipt_reprints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kiosk_requisition_print_job_id');
                $table->uuid('attempt_token')->unique();
                $table->string('printer_name')->nullable();
                $table->timestamp('printed_at');
            });
        }

        Schema::table('kiosk_requisition_receipt_reprints', function (Blueprint $table) {
            $table->foreign('kiosk_requisition_print_job_id', 'kr_receipt_reprints_job_fk')
                ->references('id')
                ->on('kiosk_requisition_print_jobs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_requisition_receipt_reprints');
    }
};
