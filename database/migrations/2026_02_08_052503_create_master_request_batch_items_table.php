<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_request_batch_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('master_print_batch_id')->constrained('master_print_batches')->cascadeOnDelete();
            $table->foreignId('master_request_folio_id')->constrained('master_request_folios')->cascadeOnDelete();

            $table->unsignedInteger('copies')->default(1);

            $table->timestamps();

            $table->unique(
                ['master_print_batch_id', 'master_request_folio_id'],
                'mrbi_batch_folio_unique'
            );

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_request_batch_items');
    }
};
