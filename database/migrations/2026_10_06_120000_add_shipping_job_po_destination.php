<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('label_request_lpk_shipping_items', function (Blueprint $table) {
            $table->string('po_number', 80)->nullable()->after('model');
            $table->string('destination', 120)->nullable()->after('po_number');
        });

        DB::table('label_request_lpk_shipping_groups')->select('id', 'po_number', 'destination')
            ->orderBy('id')->chunkById(500, function ($groups): void {
                foreach ($groups as $group) {
                    DB::table('label_request_lpk_shipping_items')
                        ->where('label_request_lpk_shipping_group_id', $group->id)
                        ->update(['po_number' => $group->po_number, 'destination' => $group->destination]);
                }
            });

        Schema::table('label_job_entries', function (Blueprint $table) {
            $table->string('destination', 120)->nullable()->after('po_number');
        });
    }

    public function down(): void
    {
        Schema::table('label_job_entries', fn (Blueprint $table) => $table->dropColumn('destination'));
        Schema::table('label_request_lpk_shipping_items', fn (Blueprint $table) => $table->dropColumn(['po_number', 'destination']));
    }
};
