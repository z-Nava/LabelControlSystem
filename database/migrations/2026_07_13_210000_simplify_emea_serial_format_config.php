<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('sku_serial_format_emea')) {
            return;
        }

        if (
            Schema::hasColumn('sku_serial_format_emea', 'plant_code')
            && Schema::hasColumn('sku_serial_format_emea', 'prefix_value')
            && Schema::hasColumn('sku_serial_format_emea', 'conformity_code')
        ) {
            DB::table('sku_serial_format_emea')
                ->select(['id', 'prefix_value', 'conformity_code', 'plant_code'])
                ->orderBy('id')
                ->each(function ($row): void {
                    $prefix = strtoupper(trim((string) $row->prefix_value));
                    $middle = strtoupper(trim((string) $row->conformity_code));
                    $conformity = strtoupper(trim((string) $row->plant_code));

                    if (preg_match('/^\d{4}$/', $prefix) && preg_match('/^\d{2}$/', $middle) && preg_match('/^\d{2}$/', $conformity)) {
                        DB::table('sku_serial_format_emea')
                            ->where('id', $row->id)
                            ->update([
                                'prefix_value' => $prefix . $middle,
                                'conformity_code' => $conformity,
                            ]);
                    }
                });
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('sku_serial_format_emea', 'prefix_source') ? 'prefix_source' : null,
            Schema::hasColumn('sku_serial_format_emea', 'prefix_digits') ? 'prefix_digits' : null,
            Schema::hasColumn('sku_serial_format_emea', 'plant_code') ? 'plant_code' : null,
            Schema::hasColumn('sku_serial_format_emea', 'declaration_required') ? 'declaration_required' : null,
            Schema::hasColumn('sku_serial_format_emea', 'print_format') ? 'print_format' : null,
            Schema::hasColumn('sku_serial_format_emea', 'reset_scope') ? 'reset_scope' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('sku_serial_format_emea', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sku_serial_format_emea')) {
            return;
        }

        Schema::table('sku_serial_format_emea', function (Blueprint $table) {
            if (!Schema::hasColumn('sku_serial_format_emea', 'prefix_source')) {
                $table->string('prefix_source', 30)->nullable()->after('prefix_value');
            }

            if (!Schema::hasColumn('sku_serial_format_emea', 'prefix_digits')) {
                $table->unsignedTinyInteger('prefix_digits')->nullable()->after('prefix_source');
            }

            if (!Schema::hasColumn('sku_serial_format_emea', 'plant_code')) {
                $table->string('plant_code', 10)->nullable()->after('conformity_code');
            }

            if (!Schema::hasColumn('sku_serial_format_emea', 'declaration_required')) {
                $table->boolean('declaration_required')->default(false)->after('unit_digits');
            }

            if (!Schema::hasColumn('sku_serial_format_emea', 'print_format')) {
                $table->string('print_format', 20)->nullable()->after('declaration_required');
            }

            if (!Schema::hasColumn('sku_serial_format_emea', 'reset_scope')) {
                $table->string('reset_scope', 20)->default('monthly')->after('print_format');
            }
        });

        DB::table('sku_serial_format_emea')->update([
            'prefix_source' => 'fixed_value',
            'prefix_digits' => 6,
            'declaration_required' => false,
            'print_format' => 'spaces',
            'reset_scope' => 'monthly',
        ]);
    }
};
