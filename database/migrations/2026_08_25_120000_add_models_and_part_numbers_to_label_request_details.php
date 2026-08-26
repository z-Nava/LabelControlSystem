<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('label_request_serials', function (Blueprint $table) {
            $table->string('model', 80)->nullable()->after('part_number');
        });

        Schema::table('label_request_ratings', function (Blueprint $table) {
            $table->string('model', 80)->nullable()->after('part_number');
        });

        Schema::table('label_request_shipping_items', function (Blueprint $table) {
            $table->string('model', 80)->nullable()->after('item_reference');
        });

        Schema::table('label_requests', function (Blueprint $table) {
            $table->string('inner_part_number', 80)->nullable()->after('serial_part_number');
            $table->string('inner_model', 80)->nullable()->after('inner_part_number');
            $table->string('shipping_part_number', 80)->nullable()->after('inner_model');
            $table->string('shipping_model', 80)->nullable()->after('shipping_part_number');

            $table->index('inner_part_number', 'idx_label_requests_inner_part_number');
            $table->index('shipping_part_number', 'idx_label_requests_shipping_part_number');
        });

        DB::table('label_requests')
            ->select(['id', 'model', 'include_inner', 'include_shipping'])
            ->whereNotNull('model')
            ->where('model', '<>', '')
            ->orderBy('id')
            ->chunkById(500, function ($requests): void {
                foreach ($requests as $request) {
                    DB::table('label_request_serials')
                        ->where('label_request_id', $request->id)
                        ->update(['model' => $request->model]);

                    DB::table('label_request_ratings')
                        ->where('label_request_id', $request->id)
                        ->update(['model' => $request->model]);

                    DB::table('label_request_shipping_items')
                        ->where('label_request_id', $request->id)
                        ->update(['model' => $request->model]);

                    DB::table('label_requests')
                        ->where('id', $request->id)
                        ->update([
                            'inner_model' => $request->include_inner ? $request->model : null,
                            'shipping_model' => $request->include_shipping ? $request->model : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('label_requests', function (Blueprint $table) {
            $table->dropIndex('idx_label_requests_shipping_part_number');
            $table->dropIndex('idx_label_requests_inner_part_number');
            $table->dropColumn([
                'shipping_model',
                'shipping_part_number',
                'inner_model',
                'inner_part_number',
            ]);
        });

        Schema::table('label_request_shipping_items', function (Blueprint $table) {
            $table->dropColumn('model');
        });

        Schema::table('label_request_ratings', function (Blueprint $table) {
            $table->dropColumn('model');
        });

        Schema::table('label_request_serials', function (Blueprint $table) {
            $table->dropColumn('model');
        });
    }
};
