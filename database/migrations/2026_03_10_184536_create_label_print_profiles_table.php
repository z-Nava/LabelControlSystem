<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('label_print_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('label_sku_id')
                ->constrained('label_skus')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Si quieres perfiles distintos por tipo (serial/rating), úsalo.
            // Si no, déjalo NULL y aplica “perfil general” por SKU.
            $table->string('label_type', 20)->nullable()->index();
            $table->string('serial_standard', 10)->default('UL')->index();

            $table->string('name', 120);

            // Impresora (lo puedes usar para sugerir/default)
            $table->string('default_printer_name', 120)->nullable();
            $table->string('default_printer_ip', 45)->nullable(); // IPv4/IPv6

            // Ajustes típicos Zebra
            $table->unsignedSmallInteger('dpi')->default(203);
            $table->unsignedTinyInteger('darkness')->nullable(); // 0-30 aprox (depende modelo)
            $table->unsignedTinyInteger('speed')->nullable();    // 1-14 aprox (depende modelo)

            // Media/print settings
            $table->string('media_type', 40)->nullable(); // gap, mark, continuous
            $table->string('print_mode', 40)->nullable(); // tear-off, peel-off, cutter...

            // Offsets (en dots o mm; define tu estándar. Aquí lo dejo en dots por DPI)
            $table->integer('offset_x')->default(0);
            $table->integer('offset_y')->default(0);

            // JSON libre para settings extra (calibration flags, etc.)
            $table->json('settings')->nullable();

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

            $table->index(['label_sku_id', 'label_type', 'serial_standard', 'is_active']);
            $table->unique(['label_sku_id', 'label_type', 'serial_standard', 'name'], 'uq_prof_sku_type_std_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_profiles');
    }
};
