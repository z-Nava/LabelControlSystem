<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('label_print_block_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('label_print_block_id')
                ->constrained('label_print_blocks')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('label_print_batch_item_id')
                ->constrained('label_print_batch_items')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->unique(['label_print_block_id', 'label_print_batch_item_id'], 'uq_label_print_block_items_block_item');
            $table->index('label_print_batch_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_block_items');
    }
};
