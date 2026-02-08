<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_request_folios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('master_request_id')->constrained('master_requests')->cascadeOnDelete();

            $table->unsignedInteger('folio_number');
            $table->boolean('is_partial')->default(false);
            $table->unsignedInteger('qty_for_folio')->nullable();

            $table->string('status', 30)->default('pending'); // pending|printed

            $table->timestamps();

            $table->unique(['master_request_id', 'folio_number']); // evita duplicados dentro de la requisición
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_request_folios');
    }
};
