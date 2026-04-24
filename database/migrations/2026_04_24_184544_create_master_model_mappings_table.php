<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_model_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('np', 40);
            $table->string('sku', 80);
            $table->string('master_sheet_type', 40);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['np', 'sku', 'master_sheet_type'], 'master_model_unique_np_sku_type');
            $table->index(['master_sheet_type', 'active']);
            $table->index(['np', 'master_sheet_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_model_mappings');
    }
};
