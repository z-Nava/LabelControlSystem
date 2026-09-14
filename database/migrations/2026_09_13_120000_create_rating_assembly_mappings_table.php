<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rating_assembly_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('rating_part_number', 80);
            $table->string('assembly_part_number', 80);
            $table->string('market', 10);
            $table->boolean('active')->default(true);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['rating_part_number', 'assembly_part_number', 'market'],
                'uq_rating_assembly_market'
            );
            $table->index(
                ['assembly_part_number', 'market', 'active'],
                'idx_rating_assembly_lookup'
            );
            $table->index(['rating_part_number', 'active'], 'idx_rating_part_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_assembly_mappings');
    }
};
