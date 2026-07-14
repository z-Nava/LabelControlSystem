<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sku_serial_format_anz', function (Blueprint $table) {
            $table->string('customer_tool_code', 40)->nullable()->change();
            $table->boolean('customer_tool_code_required')->default(true)->change();
        });

        DB::table('sku_serial_format_anz')->update([
            'tool_version_required' => true,
            'customer_tool_code_required' => true,
            'unit_digits' => 5,
            'qr_separator' => ' | ',
            'include_customer_tool_code_in_qr' => true,
            'print_format' => 'spaces',
            'reset_scope' => 'monthly',
            'updated_at' => now(),
        ]);

        DB::table('sku_serial_formats')
            ->where(function ($query) {
                $query->where('serial_standard', 'ANZ')
                    ->orWhere('market', 'ANZ')
                    ->orWhere('serial_scheme', 'anz_standard');
            })
            ->update([
                'serial_length' => 23,
                'date_mode' => 'month_year',
                'month_letter_enabled' => true,
                'month_letter_map' => 'A,B,C,D,E,F,G,H,J,K,L,M',
                'year_digits' => 4,
                'week_digits' => 2,
                'include_year' => true,
                'include_week' => false,
                'unit_length' => 5,
                'unit_digits' => 5,
                'qr_payload_format' => 'customer_tool_code_serial',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('sku_serial_format_anz', function (Blueprint $table) {
            $table->string('customer_tool_code', 10)->nullable()->change();
            $table->boolean('customer_tool_code_required')->default(false)->change();
        });
    }
};
