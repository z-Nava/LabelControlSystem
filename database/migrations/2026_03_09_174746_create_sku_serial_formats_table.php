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

            $table->string('prefix', 10)->nullable();        // PPP
            $table->string('serial_break', 10)->nullable();  // C
            $table->string('plant_code', 10)->nullable();    // PL

            $table->string('pattern', 80)
                ->default('{PPP}{C}{PL}{YY}{WW}{SSSSS}');

            $table->unsignedTinyInteger('unit_length')->default(5);
            $table->unsignedInteger('next_unit')->default(1);

            $table->boolean('is_active')->default(true);

            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->index(['sku', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_serial_formats');
    }
};