<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('label_templates', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);

            // serial | rating | shipping
            $table->string('label_type', 20)->index();
            $table->string('serial_standard', 10)->default('UL')->index();

            // Template puede ser global (null) o por SKU (FK)
            $table->foreignId('label_sku_id')
                ->nullable()
                ->constrained('label_skus')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Metadatos de impresión
            $table->unsignedSmallInteger('dpi')->default(203); // 203 / 300
            $table->decimal('width_mm', 8, 2)->nullable();
            $table->decimal('height_mm', 8, 2)->nullable();

            // ZPL base (con placeholders del tipo {{serial_full}}, {{label_part_number}}, etc.)
            $table->longText('zpl');

            // Layout estructurado para la etiqueta serial (QR, SKU, serial, etc.)
            $table->json('serial_layout')->nullable();

            // Variables/metadata extra (opcional)
            $table->json('meta')->nullable();

            // versionado
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            // Índices útiles
            $table->index(['label_sku_id', 'label_type', 'serial_standard', 'is_active']);
            $table->unique(['label_sku_id', 'label_type', 'serial_standard', 'version'], 'uq_tpl_sku_type_std_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_templates');
    }
};
