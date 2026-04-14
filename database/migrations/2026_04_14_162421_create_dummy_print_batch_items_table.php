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
        Schema::create('dummy_print_batch_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dummy_print_batch_id')
                ->constrained('dummy_print_batches')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('dummy_request_item_id')
                ->constrained('dummy_request_items')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedSmallInteger('copies')->default(1);

            $table->timestamps();

            $table->unique(['dummy_print_batch_id', 'dummy_request_item_id'], 'dummy_batch_item_unique');
            $table->index('dummy_request_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dummy_print_batch_items');
    }
};
