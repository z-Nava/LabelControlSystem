<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('label_print_batch_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('label_print_batch_id')
                ->constrained('label_print_batches')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Fase 1: este FK existe en tu diagrama, pero serial_units es Fase 2.
            // Lo dejamos nullable SIN foreign key por ahora.
            $table->unsignedBigInteger('serial_unit_id')->nullable();

            // Qué se imprimió en este item
            $table->boolean('print_serial')->default(false);
            $table->boolean('print_rating')->default(false);

            // copias del item (útil para rating y/o reimpresión)
            $table->unsignedSmallInteger('copies')->default(1);

            $table->timestamps();

            $table->index('label_print_batch_id');
            $table->index('serial_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_batch_items');
    }
};