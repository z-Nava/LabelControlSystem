<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SOURCE_LABEL_ROOM = 'label_room';

    private const SOURCE_KIOSK = 'kiosk';

    public function up(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->date('request_date')->nullable()->change();
            $table->foreignId('shift_id')->nullable()->change();
            $table->string('leader_name', 120)->nullable()->change();
            $table->string('request_source', 20)
                ->default(self::SOURCE_LABEL_ROOM)
                ->after('leader_name');

            $table->index('request_source');
        });

        Schema::table('master_print_batches', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->change();
        });

        $kioskUserIds = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.name', self::SOURCE_KIOSK)
            ->pluck('role_user.user_id');

        if ($kioskUserIds->isNotEmpty()) {
            DB::table('master_requests')
                ->whereIn('requested_by_user_id', $kioskUserIds)
                ->update(['request_source' => self::SOURCE_KIOSK]);
        }
    }

    public function down(): void
    {
        Schema::table('master_print_batches', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable(false)->change();
        });

        Schema::table('master_requests', function (Blueprint $table) {
            $table->dropIndex(['request_source']);
            $table->dropColumn('request_source');
            $table->date('request_date')->nullable(false)->change();
            $table->foreignId('shift_id')->nullable(false)->change();
            $table->string('leader_name', 120)->nullable(false)->change();
        });
    }
};
