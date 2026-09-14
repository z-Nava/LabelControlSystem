<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serial_weeks', function (Blueprint $table) {
            // The table name and legacy week column remain to preserve historical foreign keys.
            // Existing rows remain null: their true serial period cannot be inferred safely.
            $table->string('period_type', 10)->nullable()->after('serial_standard');
            $table->unsignedTinyInteger('period_number')->nullable()->after('year');
        });

        Schema::table('serial_weeks', function (Blueprint $table) {
            $table->dropUnique('uq_serial_weeks_family_period');
            $table->unique(
                ['label_part_number', 'serial_standard', 'year', 'period_type', 'period_number'],
                'uq_serial_periods_rating_market_period'
            );
            $table->index(
                ['serial_standard', 'period_type', 'year', 'period_number'],
                'idx_serial_periods_market_period'
            );
        });

        Schema::table('label_requests', function (Blueprint $table) {
            $table->string('serial_period_type', 10)->nullable()->after('control_week');
            $table->unsignedSmallInteger('serial_period_year')->nullable()->after('serial_period_type');
            $table->unsignedTinyInteger('serial_period_number')->nullable()->after('serial_period_year');
        });

        Schema::table('label_work_tasks', function (Blueprint $table) {
            $table->string('serial_period_type', 10)->nullable()->after('control_week');
            $table->unsignedSmallInteger('serial_period_year')->nullable()->after('serial_period_type');
            $table->unsignedTinyInteger('serial_period_number')->nullable()->after('serial_period_year');
        });
    }

    public function down(): void
    {
        Schema::table('label_work_tasks', function (Blueprint $table) {
            $table->dropColumn(['serial_period_type', 'serial_period_year', 'serial_period_number']);
        });

        Schema::table('label_requests', function (Blueprint $table) {
            $table->dropColumn(['serial_period_type', 'serial_period_year', 'serial_period_number']);
        });

        Schema::table('serial_weeks', function (Blueprint $table) {
            $table->dropIndex('idx_serial_periods_market_period');
            $table->dropUnique('uq_serial_periods_rating_market_period');
            $table->unique(
                ['label_part_number', 'folio_family', 'year', 'week'],
                'uq_serial_weeks_family_period'
            );
            $table->dropColumn(['period_type', 'period_number']);
        });
    }
};
