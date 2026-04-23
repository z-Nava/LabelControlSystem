<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sku_serial_formats', function (Blueprint $table) {
            $table->string('description', 160)->nullable()->after('serial_scheme');
            $table->unsignedSmallInteger('serial_length')->nullable()->after('description');
            $table->string('qr_payload_format', 40)->default('serial_only')->after('serial_length');

            $table->string('date_mode', 20)->default('year_week')->after('qr_payload_format');
            $table->boolean('month_letter_enabled')->default(false)->after('date_mode');
            $table->string('month_letter_map', 40)->nullable()->after('month_letter_enabled');

            $table->unsignedTinyInteger('unit_digits')->nullable()->after('unit_length');

            $table->unsignedTinyInteger('ul_prefix_length')->nullable()->after('ul_prefix');
            $table->boolean('ul_use_plant_code')->default(true)->after('ul_plant_code');

            $table->string('emea_prefix_source', 30)->nullable()->after('emea_prefix');
            $table->unsignedTinyInteger('emea_prefix_digits')->nullable()->after('emea_prefix_source');
            $table->unsignedTinyInteger('emea_unit_digits')->nullable()->after('emea_prefix_digits');
            $table->boolean('emea_declaration_required')->default(false)->after('emea_unit_digits');

            $table->string('anz_product_prefix', 20)->nullable()->after('anz_customer_tool_code');
            $table->string('anz_tool_version', 2)->nullable()->after('anz_product_prefix');
            $table->boolean('anz_tool_version_required')->default(false)->after('anz_tool_version');
            $table->unsignedTinyInteger('anz_unit_digits')->nullable()->after('anz_tool_version_required');
            $table->string('anz_qr_separator', 5)->nullable()->after('anz_unit_digits');
            $table->boolean('anz_include_customer_tool_code_in_qr')->default(true)->after('anz_qr_separator');
            $table->string('anz_serial_print_format', 20)->nullable()->after('anz_include_customer_tool_code_in_qr');
        });

        DB::table('sku_serial_formats')->update([
            'unit_digits' => DB::raw('unit_length'),
            'month_letter_map' => 'A,B,C,D,E,F,G,H,J,K,L,M',
        ]);

        DB::table('sku_serial_formats')->where('serial_standard', 'UL')->update([
            'date_mode' => 'year_week',
            'qr_payload_format' => 'serial_only',
            'month_letter_enabled' => false,
        ]);

        DB::table('sku_serial_formats')->where('serial_standard', 'EMEA')->update([
            'date_mode' => 'month_year',
            'qr_payload_format' => 'emea_code_only',
            'month_letter_enabled' => true,
            'emea_prefix_source' => 'fixed_value',
        ]);

        DB::table('sku_serial_formats')->where('serial_standard', 'ANZ')->update([
            'date_mode' => 'month_year',
            'qr_payload_format' => 'customer_tool_code_serial',
            'month_letter_enabled' => true,
            'anz_product_prefix' => DB::raw('emea_prefix'),
            'anz_tool_version' => DB::raw('emea_conformity_code'),
            'anz_unit_digits' => DB::raw('unit_length'),
            'anz_qr_separator' => ' | ',
            'anz_serial_print_format' => 'spaces',
            'anz_include_customer_tool_code_in_qr' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('sku_serial_formats', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'serial_length',
                'qr_payload_format',
                'date_mode',
                'month_letter_enabled',
                'month_letter_map',
                'unit_digits',
                'ul_prefix_length',
                'ul_use_plant_code',
                'emea_prefix_source',
                'emea_prefix_digits',
                'emea_unit_digits',
                'emea_declaration_required',
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
};