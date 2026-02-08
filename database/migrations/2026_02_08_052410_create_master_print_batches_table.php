<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_print_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('master_request_id')->constrained('master_requests')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts');

            $table->string('batch_type', 20); // print|reprint|rework
            $table->text('reason')->nullable();

            $table->foreignId('printed_by_user_id')->nullable()->constrained('users');
            $table->string('printed_by_name', 120)->nullable();

            $table->dateTime('printed_at')->nullable();

            $table->timestamps();

            $table->index(['master_request_id', 'printed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_print_batches');
    }
};
