<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_lines', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();   // MXC007, MXMR003, etc.
            $table->string('name', 120);            // Nombre legible
            $table->string('line_type', 40);        // batteries, consoles, motors, ops, hydraulics...
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index(['line_type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_lines');
    }
};
