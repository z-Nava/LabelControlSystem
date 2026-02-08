<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_requests', function (Blueprint $table) {
            $table->id();

            $table->date('request_date');
            $table->unsignedTinyInteger('week');

            $table->foreignId('line_id')->constrained('production_lines');
            $table->foreignId('shift_id')->constrained('shifts');

            $table->string('leader_name', 120);

            $table->string('requested_by_name', 120);
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users');

            // PO del formato físico (en tu caso = Custom PO de Oracle)
            $table->string('po_number', 80)->nullable();

            $table->string('job_assembly', 40)->nullable();
            $table->string('job_packaging', 40)->nullable();

            $table->string('destination', 80)->nullable(); // ship_code

            $table->unsignedInteger('folios_from')->nullable();
            $table->unsignedInteger('folios_to')->nullable();

            $table->unsignedInteger('std_pack_qty')->nullable();

            $table->unsignedInteger('partial_folio')->nullable();
            $table->unsignedInteger('partial_qty')->nullable();

            // tipo de master (los 4)
            $table->string('request_type', 40);

            // nuevo / reposición (según el papel)
            $table->string('kind', 20)->default('new'); // new|reposition

            // requested -> in_progress -> completed/cancelled
            $table->string('status', 30)->default('requested');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['request_date', 'line_id']);
            $table->index(['job_assembly']);
            $table->index(['job_packaging']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_requests');
    }
};
