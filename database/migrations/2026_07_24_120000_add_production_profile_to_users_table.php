<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('production_line_id')
                ->nullable()
                ->after('shift_id')
                ->constrained('production_lines')
                ->nullOnDelete();
            $table->string('position', 32)->nullable()->after('production_line_id');

            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['position']);
            $table->dropConstrainedForeignId('production_line_id');
            $table->dropColumn('position');
        });
    }
};
