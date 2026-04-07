<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sku_serial_formats', function (Blueprint $table) {
            $table->id();

            $table->string('sku', 80)->index();
            $table->string('serial_standard', 10)->default('UL'); // UL | EMEA
            $table->string('serial_scheme', 20)->default('ul_standard'); // ul_standard | emea_rating

            // Componentes base del serial (separados)
            $table->string('prefix', 10)->nullable();        // PPP (ej: 628)
            $table->string('serial_break', 10)->nullable();  // C   (ej: C o D)
            $table->string('plant_code', 10)->nullable();    // PL  (ej: 8)

            // Configuración para construir el serial_full sin llaves
            $table->string('separator', 5)->default('');     // '' o '-' o ' '
            $table->unsignedTinyInteger('year_digits')->default(2); // 2=YY, 4=YYYY
            $table->unsignedTinyInteger('week_digits')->default(2); // 2=WW
            $table->boolean('include_year')->default(true);
            $table->boolean('include_week')->default(true);

            // (Opcional/legacy) si quieres seguir guardando una "plantilla"
            // ya NO la uses para construir el serial_full si te estaba causando llaves
            $table->string('pattern', 80)->nullable();

            // Control del consecutivo (SSSSS)
            $table->unsignedTinyInteger('unit_length')->default(5);
            $table->unsignedInteger('next_unit')->default(1);

            $table->boolean('is_active')->default(true);

            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->index(['sku', 'serial_standard', 'is_active']);
            $table->unique(['sku', 'serial_standard'], 'uq_sku_serial_format_sku_standard');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_serial_formats');
    }
};
