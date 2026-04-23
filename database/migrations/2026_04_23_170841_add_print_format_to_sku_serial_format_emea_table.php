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

        if (!Schema::hasColumn('sku_serial_format_emea', 'print_format')) {
        Schema::table('sku_serial_format_emea', function (Blueprint $table) {
            $table->string('print_format', 20)->nullable();
        });
        }

        DB::table('sku_serial_format_emea')
            ->whereNull('print_format')
            ->update(['print_format' => 'spaces']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('sku_serial_format_emea') || !Schema::hasColumn('sku_serial_format_emea', 'print_format')) {
            return;
        }

        Schema::table('sku_serial_format_emea', function (Blueprint $table) {
            $table->dropColumn('print_format');
        });
    }
};
