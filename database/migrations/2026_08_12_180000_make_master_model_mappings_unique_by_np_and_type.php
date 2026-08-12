<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_UNIQUE = 'master_model_unique_np_sku_type';

    private const NEW_UNIQUE = 'master_model_unique_np_type';

    public function up(): void
    {
        $duplicates = DB::table('master_model_mappings')
            ->select(['np', 'master_sheet_type'])
            ->groupBy('np', 'master_sheet_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $baseQuery = DB::table('master_model_mappings')
                ->where('np', $duplicate->np)
                ->where('master_sheet_type', $duplicate->master_sheet_type);

            $survivorId = (clone $baseQuery)
                ->where('active', true)
                ->orderByDesc('id')
                ->value('id')
                ?? (clone $baseQuery)->orderByDesc('id')->value('id');

            (clone $baseQuery)
                ->where('id', '<>', $survivorId)
                ->delete();
        }

        Schema::table('master_model_mappings', function (Blueprint $table) {
            $table->dropUnique(self::OLD_UNIQUE);
            $table->unique(['np', 'master_sheet_type'], self::NEW_UNIQUE);
        });
    }

    public function down(): void
    {
        Schema::table('master_model_mappings', function (Blueprint $table) {
            $table->dropUnique(self::NEW_UNIQUE);
            $table->unique(['np', 'sku', 'master_sheet_type'], self::OLD_UNIQUE);
        });
    }
};
