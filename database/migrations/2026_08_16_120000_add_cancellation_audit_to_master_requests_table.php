<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('status');
            $table->dateTime('cancelled_at')->nullable()->after('cancellation_reason');
            $table->foreignId('cancelled_by_user_id')
                ->nullable()
                ->after('cancelled_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('cancelled_by_name', 120)->nullable()->after('cancelled_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by_user_id');
            $table->dropColumn([
                'cancellation_reason',
                'cancelled_at',
                'cancelled_by_name',
            ]);
        });
    }
};
