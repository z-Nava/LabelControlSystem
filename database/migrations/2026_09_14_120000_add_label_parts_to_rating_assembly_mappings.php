<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rating_assembly_mappings', function (Blueprint $table) {
            $table->string('serial_part_number', 80)->nullable()->after('rating_part_number');
            $table->string('shipping_part_number', 80)->nullable()->after('serial_part_number');
            $table->string('inner_part_number', 80)->nullable()->after('shipping_part_number');
        });
    }

    public function down(): void
    {
        Schema::table('rating_assembly_mappings', function (Blueprint $table) {
            $table->dropColumn(['serial_part_number', 'shipping_part_number', 'inner_part_number']);
        });
    }
};
