<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_UNIQUE_CONSECUTIVE = 'dummy_request_items_job_number_consecutive_unique';
    private const OLD_UNIQUE_CONSECUTIVE_10D = 'dummy_request_items_job_number_consecutive_10d_unique';

    private const UNIQUE_CONSECUTIVE_BY_TYPE = 'dri_job_type_consec_uq';
    private const UNIQUE_CONSECUTIVE_10D_BY_TYPE = 'dri_job_type_consec10_uq';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dummy_request_items', function (Blueprint $table) {
            $table->dropUnique(self::OLD_UNIQUE_CONSECUTIVE);
            $table->dropUnique(self::OLD_UNIQUE_CONSECUTIVE_10D);

            $table->unique(['job_number', 'dummy_type', 'consecutive'], self::UNIQUE_CONSECUTIVE_BY_TYPE);
            $table->unique(['job_number', 'dummy_type', 'consecutive_10d'], self::UNIQUE_CONSECUTIVE_10D_BY_TYPE);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dummy_request_items', function (Blueprint $table) {
            $table->dropUnique(self::UNIQUE_CONSECUTIVE_BY_TYPE);
            $table->dropUnique(self::UNIQUE_CONSECUTIVE_10D_BY_TYPE);

            $table->unique(['job_number', 'consecutive'], self::OLD_UNIQUE_CONSECUTIVE);
            $table->unique(['job_number', 'consecutive_10d'], self::OLD_UNIQUE_CONSECUTIVE_10D);
        });
    }
};