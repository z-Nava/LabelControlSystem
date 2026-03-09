<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('serial_weeks', function (Blueprint $table) {
            $table->id();

            // En tu diagrama: controlador por label_part_number + semana/año
            $table->string('label_part_number', 80);

            $table->unsignedTinyInteger('week'); // 1-53
            $table->unsignedSmallInteger('year'); // 2024, 2025...

            // Prefix efectivo de esa semana (por si cambia el formato)
            $table->string('prefix', 10)->nullable();

            // Último serial usado (numérico consecutivo, ej. 142)
            $table->unsignedInteger('last_serial_number')->default(0);

            $table->timestamps();

            // Para impedir duplicidad por PN + semana + año
            $table->unique(['label_part_number', 'year', 'week'], 'uq_serial_weeks_pn_year_week');

            $table->index(['year', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_weeks');
    }
};