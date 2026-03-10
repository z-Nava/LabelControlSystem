<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('label_print_profile_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('label_print_profile_id')
                ->constrained('label_print_profiles')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedInteger('version')->default(1);

            // Snapshot completo del perfil (para rollback)
            $table->json('snapshot');

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->unique(['label_print_profile_id', 'version'], 'uq_profver_profile_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_print_profile_versions');
    }
};