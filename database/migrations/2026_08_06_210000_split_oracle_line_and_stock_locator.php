<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const BATTERY_LINE_LOCATORS = [
        'MXB001' => 'SMARKET-1',
        'MXB002' => 'MXB002',
        'MXB003' => 'MXB003',
        'MXB004' => 'SMARKET-1',
        'MXB005' => 'SMARKET-1',
        'MXB006' => 'SMARKET-1',
        'MXB007' => 'SMARKET-1',
        'MXB006-PRE COATING' => 'MXB006',
        'MXB004-COATING' => 'MXB004',
        'MXB004-POST COATING' => 'SMARKET-1',
        'MXB004-PRE COATING' => 'MXB004',
        'MXB005-COATING' => 'MXB004',
        'MXB005-POST COATING' => 'SMARKET-1',
        'MXB005-PRE COATING' => 'MXB004',
    ];

    public function up(): void
    {
        Schema::table('stock_locators', function (Blueprint $table) {
            $table->renameColumn('stock_locator', 'oracle_line');
        });

        Schema::table('stock_locators', function (Blueprint $table) {
            $table->string('stock_locator', 40)->nullable()->after('subinventory');
            $table->index(['stock_locator', 'active']);
        });

        DB::table('stock_locators')->update([
            'stock_locator' => DB::raw('oracle_line'),
        ]);

        $now = now();

        foreach (self::BATTERY_LINE_LOCATORS as $oracleLine => $stockLocator) {
            $mappingExists = DB::table('stock_locators')
                ->where('oracle_line', $oracleLine)
                ->exists();
            $values = [
                'subinventory' => 'WIP',
                'stock_locator' => $stockLocator,
                'active' => true,
                'updated_at' => $now,
            ];

            if ($mappingExists) {
                DB::table('stock_locators')
                    ->where('oracle_line', $oracleLine)
                    ->update($values);
            } else {
                DB::table('stock_locators')->insert([
                    'oracle_line' => $oracleLine,
                    ...$values,
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('stock_locators', function (Blueprint $table) {
            $table->dropIndex(['stock_locator', 'active']);
            $table->dropColumn('stock_locator');
        });

        Schema::table('stock_locators', function (Blueprint $table) {
            $table->renameColumn('oracle_line', 'stock_locator');
        });
    }
};
