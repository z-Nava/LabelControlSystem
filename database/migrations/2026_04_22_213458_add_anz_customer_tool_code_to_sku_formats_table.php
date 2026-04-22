<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sku_serial_formats', function (Blueprint $table) {
            $table->string('anz_customer_tool_code', 10)
                ->nullable()
                ->after('emea_plant_code');
        });
    }

    public function down(): void
    {
        Schema::table('sku_serial_formats', function (Blueprint $table) {
            $table->dropColumn('anz_customer_tool_code');
        });
    }
};
