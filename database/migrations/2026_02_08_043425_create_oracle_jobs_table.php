<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('oracle_jobs', function (Blueprint $table) {
            $table->id();

            $table->string('job_number', 40)->unique();

            $table->string('line', 30)->nullable();
            $table->string('job_status', 40)->nullable();

            $table->string('assembly', 80)->nullable();
            $table->string('bom_revision', 40)->nullable();

            $table->text('part_description')->nullable();

            $table->integer('job_qty')->nullable();
            $table->integer('qty_completed')->nullable();
            $table->integer('quantity_remainder')->nullable();

            $table->dateTime('scheduled_start_date')->nullable();
            $table->dateTime('last_update_date')->nullable();

            $table->text('job_description')->nullable();

            $table->string('ttl_cust_po', 80)->nullable();
            $table->string('ship_to', 160)->nullable();
            $table->string('ship_code', 80)->nullable();
            $table->text('ship_address')->nullable();

            $table->string('source_file_name', 190)->nullable();
            $table->dateTime('imported_at')->nullable();

            $table->timestamps();

            $table->index(['line', 'job_status']);
            $table->index(['scheduled_start_date']);
            $table->index(['last_update_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oracle_jobs');
    }
};
