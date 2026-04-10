<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('serial_units', function (Blueprint $table) {
            $table->id();

            $table->foreignId('serial_week_id')
                ->constrained('serial_weeks')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('label_sku_id')
                ->nullable()
                ->constrained('label_skus')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('label_part_number', 80)->nullable();
            $table->string('serial_standard', 10)->default('UL');

            // consecutivo numérico (ej 142)
            $table->unsignedInteger('serial_number');

            // serial completo formateado (PPP C PL YY WW SSSSS)
            $table->string('serial_full', 80);
            $table->string('rating_qr_code', 120)->nullable();

            $table->enum('status', ['allocated', 'printed'])->default('allocated');
            $table->timestamp('printed_at')->nullable();

            $table->timestamps();

            // Un serial_full no debe repetirse
            $table->unique('serial_full', 'uq_serial_units_serial_full');

            // Dentro de la misma semana, un consecutivo no debe repetirse
            $table->unique(['serial_week_id', 'serial_number'], 'uq_serial_units_week_number');

            $table->index(['serial_week_id', 'status']);
            $table->index(['label_sku_id', 'status']);
            $table->index(['label_part_number', 'serial_standard']);
            $table->index('printed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_units');
    }
};
