<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_request_shipping_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_request_id')
                ->constrained('label_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('item_reference', 120);
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->unique(
                ['label_request_id', 'item_reference'],
                'uq_label_request_shipping_item',
            );
            $table->index(
                ['label_request_id', 'position'],
                'idx_label_request_shipping_item_position',
            );
            $table->index('item_reference', 'idx_label_request_shipping_item_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_request_shipping_items');
    }
};
