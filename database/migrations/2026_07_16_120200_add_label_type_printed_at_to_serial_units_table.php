<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('serial_units', function (Blueprint $table) {
            $table->timestamp('serial_printed_at')->nullable()->after('printed_at');
            $table->timestamp('rating_printed_at')->nullable()->after('serial_printed_at');
        });

        DB::table('serial_units')
            ->whereNotNull('printed_at')
            ->update([
                'serial_printed_at' => DB::raw('printed_at'),
                'rating_printed_at' => DB::raw('printed_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('serial_units', function (Blueprint $table) {
            $table->dropColumn(['serial_printed_at', 'rating_printed_at']);
        });
    }
};
