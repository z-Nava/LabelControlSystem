<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sku_serial_formats', function (Blueprint $table) {
            $table->string('market', 10)->nullable()->after('sku');
        });

        DB::table('sku_serial_formats')->update([
            'market' => DB::raw('serial_standard'),
        ]);

        Schema::table('sku_serial_formats', function (Blueprint $table) {
            $table->index(['sku', 'market', 'is_active'], 'idx_sku_serial_formats_sku_market_active');
        });

        Schema::create('sku_serial_format_ul', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_serial_format_id')
                ->constrained('sku_serial_formats')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('prefix', 10)->nullable();
            $table->unsignedTinyInteger('prefix_length')->nullable();
            $table->string('serial_break', 10)->nullable();
            $table->string('plant_code', 10)->nullable();
            $table->boolean('use_plant_code')->default(true);
            $table->string('reset_scope', 20)->default('weekly');
            $table->string('pattern', 80)->nullable();
            $table->timestamps();

            $table->unique('sku_serial_format_id', 'uq_sku_serial_format_ul_parent');
        });

        Schema::create('sku_serial_format_emea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_serial_format_id')
                ->constrained('sku_serial_formats')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('prefix_value', 20)->nullable();
            $table->string('prefix_source', 30)->nullable();
            $table->unsignedTinyInteger('prefix_digits')->nullable();
            $table->string('conformity_code', 10)->nullable();
            $table->string('plant_code', 10)->nullable();
            $table->unsignedTinyInteger('unit_digits')->nullable();
            $table->boolean('declaration_required')->default(false);
            $table->string('reset_scope', 20)->default('monthly');
            $table->string('pattern', 120)->nullable();
            $table->timestamps();

            $table->unique('sku_serial_format_id', 'uq_sku_serial_format_emea_parent');
        });

        Schema::create('sku_serial_format_anz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_serial_format_id')
                ->constrained('sku_serial_formats')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('product_prefix', 20)->nullable();
            $table->unsignedTinyInteger('product_prefix_length')->nullable();
            $table->string('tool_version_letter', 2)->nullable();
            $table->boolean('tool_version_required')->default(true);
            $table->string('customer_tool_code', 10)->nullable();
            $table->boolean('customer_tool_code_required')->default(false);
            $table->unsignedTinyInteger('unit_digits')->nullable();
            $table->string('qr_separator', 5)->nullable();
            $table->boolean('include_customer_tool_code_in_qr')->default(true);
            $table->string('print_format', 20)->nullable();
            $table->string('reset_scope', 20)->default('monthly');
            $table->string('pattern', 120)->nullable();
            $table->string('qr_pattern', 160)->nullable();
            $table->timestamps();

            $table->unique('sku_serial_format_id', 'uq_sku_serial_format_anz_parent');
        });

        $formats = DB::table('sku_serial_formats')
            ->select([
                'id',
                'serial_standard',
                'pattern',
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
                'anz_product_prefix',
                'anz_tool_version',
                'anz_tool_version_required',
                'anz_customer_tool_code',
                'anz_unit_digits',
                'anz_qr_separator',
                'anz_include_customer_tool_code_in_qr',
                'anz_serial_print_format',
            ])
            ->get();

        foreach ($formats as $format) {
            $standard = strtoupper((string) $format->serial_standard);

            if ($standard === 'UL') {
                DB::table('sku_serial_format_ul')->insert([
                    'sku_serial_format_id' => $format->id,
                    'prefix' => $format->ul_prefix,
                    'prefix_length' => $format->ul_prefix_length,
                    'serial_break' => $format->ul_serial_break,
                    'plant_code' => $format->ul_plant_code,
                    'use_plant_code' => (bool) $format->ul_use_plant_code,
                    'reset_scope' => 'weekly',
                    'pattern' => $format->pattern,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            if ($standard === 'EMEA') {
                DB::table('sku_serial_format_emea')->insert([
                    'sku_serial_format_id' => $format->id,
                    'prefix_value' => $format->emea_prefix,
                    'prefix_source' => $format->emea_prefix_source ?: 'fixed_value',
                    'prefix_digits' => $format->emea_prefix_digits,
                    'conformity_code' => $format->emea_conformity_code,
                    'plant_code' => $format->emea_plant_code,
                    'unit_digits' => $format->emea_unit_digits,
                    'declaration_required' => (bool) $format->emea_declaration_required,
                    'reset_scope' => 'monthly',
                    'pattern' => $format->pattern,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('sku_serial_format_anz')->insert([
                'sku_serial_format_id' => $format->id,
                'product_prefix' => $format->anz_product_prefix ?: $format->emea_prefix,
                'product_prefix_length' => $format->emea_prefix_digits,
                'tool_version_letter' => $format->anz_tool_version ?: $format->emea_conformity_code,
                'tool_version_required' => (bool) $format->anz_tool_version_required,
                'customer_tool_code' => $format->anz_customer_tool_code,
                'customer_tool_code_required' => false,
                'unit_digits' => $format->anz_unit_digits,
                'qr_separator' => $format->anz_qr_separator ?: ' | ',
                'include_customer_tool_code_in_qr' => (bool) $format->anz_include_customer_tool_code_in_qr,
                'print_format' => $format->anz_serial_print_format ?: 'spaces',
                'reset_scope' => 'monthly',
                'pattern' => $format->pattern,
                'qr_pattern' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_serial_format_anz');
        Schema::dropIfExists('sku_serial_format_emea');
        Schema::dropIfExists('sku_serial_format_ul');

        Schema::table('sku_serial_formats', function (Blueprint $table) {
            $table->dropIndex('idx_sku_serial_formats_sku_market_active');
            $table->dropColumn('market');
        });
    }
};
