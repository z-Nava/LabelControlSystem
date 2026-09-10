<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_model_mappings', function (Blueprint $table) {
            $table->dropColumn('folio_family');
        });
    }

    public function down(): void
    {
        Schema::table('master_model_mappings', function (Blueprint $table) {
            $table->string('folio_family', 80)->nullable();
        });
    }
};
