<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sku_serial_formats', function (Blueprint $table) {
            $table->dropColumn([
                'ul_prefix',
                'ul_prefix_length',
                'ul_serial_break',
                'ul_plant_code',
                'ul_use_plant_code',
                'emea_prefix',
                'emea_prefix_source',
                'emea_prefix_digits',
                'emea_conformity_code',
                'emea_plant_code',
                'emea_unit_digits',
                'emea_declaration_required',
                'anz_customer_tool_code',
                'anz_product_prefix',
                'anz_tool_version',
                'anz_tool_version_required',
                'anz_unit_digits',
                'anz_qr_separator',
                'anz_include_customer_tool_code_in_qr',
                'anz_serial_print_format',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('sku_serial_formats', function (Blueprint $table) {
            $table->string('ul_prefix', 10)->nullable()->after('month_letter_map');
            $table->unsignedTinyInteger('ul_prefix_length')->nullable()->after('ul_prefix');
            $table->string('ul_serial_break', 10)->nullable()->after('ul_prefix_length');
            $table->string('ul_plant_code', 10)->nullable()->after('ul_serial_break');
            $table->boolean('ul_use_plant_code')->default(true)->after('ul_plant_code');

            $table->string('emea_prefix', 20)->nullable()->after('ul_use_plant_code');
            $table->string('emea_prefix_source', 30)->nullable()->after('emea_prefix');
            $table->unsignedTinyInteger('emea_prefix_digits')->nullable()->after('emea_prefix_source');
            $table->string('emea_conformity_code', 10)->nullable()->after('emea_prefix_digits');
            $table->string('emea_plant_code', 10)->nullable()->after('emea_conformity_code');
            $table->unsignedTinyInteger('emea_unit_digits')->nullable()->after('emea_plant_code');
            $table->boolean('emea_declaration_required')->default(false)->after('emea_unit_digits');

            $table->string('anz_customer_tool_code', 10)->nullable()->after('emea_declaration_required');
            $table->string('anz_product_prefix', 20)->nullable()->after('anz_customer_tool_code');
            $table->string('anz_tool_version', 2)->nullable()->after('anz_product_prefix');
            $table->boolean('anz_tool_version_required')->default(false)->after('anz_tool_version');
            $table->unsignedTinyInteger('anz_unit_digits')->nullable()->after('anz_tool_version_required');
            $table->string('anz_qr_separator', 5)->nullable()->after('anz_unit_digits');
            $table->boolean('anz_include_customer_tool_code_in_qr')->default(true)->after('anz_qr_separator');
            $table->string('anz_serial_print_format', 20)->nullable()->after('anz_include_customer_tool_code_in_qr');
        });
    }
};
