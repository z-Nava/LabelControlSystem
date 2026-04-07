<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('label_skus', function (Blueprint $table) {
            $table->id();

            $table->string('sku', 80);
            $table->string('serial_standard', 10)->default('UL'); // UL | EMEA
            $table->string('label_part_number', 80);

            $table->string('description', 160)->nullable();
            $table->boolean('is_active')->default(true);

            // Opcional: trazabilidad de quién lo creó/actualizó
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['sku', 'serial_standard'], 'uq_label_skus_sku_standard');
            $table->index('label_part_number');
            $table->index(['serial_standard', 'is_active']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_skus');
    }
};
