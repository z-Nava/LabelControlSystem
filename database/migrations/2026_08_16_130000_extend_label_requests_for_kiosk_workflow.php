<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE label_requests MODIFY COLUMN status ENUM('requested','in_progress','attended','completed','cancelled') NOT NULL DEFAULT 'requested'"
            );
        }

        Schema::table('label_requests', function (Blueprint $table) {
            $table->string('label_part_number', 80)->nullable()->change();
            $table->unsignedBigInteger('folio_start')->nullable()->after('quantity_requested');
            $table->unsignedBigInteger('folio_end')->nullable()->after('folio_start');
            $table->boolean('include_shipping')->default(false)->after('include_rating');

            $table->timestamp('requisition_printed_at')->nullable()->after('status');
            $table->foreignId('requisition_printed_by_user_id')
                ->nullable()
                ->after('requisition_printed_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('attended_at')->nullable()->after('requisition_printed_by_user_id');
            $table->foreignId('attended_by_user_id')
                ->nullable()
                ->after('attended_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('delivered_at')->nullable()->after('attended_by_user_id');
            $table->foreignId('delivered_by_user_id')
                ->nullable()
                ->after('delivered_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable()->after('delivered_by_user_id');
            $table->foreignId('cancelled_by_user_id')
                ->nullable()
                ->after('cancelled_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['job_number', 'status'], 'idx_label_requests_job_status');
        });
    }

    public function down(): void
    {
        DB::table('label_requests')
            ->where('status', 'attended')
            ->update(['status' => 'in_progress']);

        Schema::table('label_requests', function (Blueprint $table) {
            $table->dropIndex('idx_label_requests_job_status');
            $table->dropConstrainedForeignId('cancelled_by_user_id');
            $table->dropConstrainedForeignId('delivered_by_user_id');
            $table->dropConstrainedForeignId('attended_by_user_id');
            $table->dropConstrainedForeignId('requisition_printed_by_user_id');
            $table->dropColumn([
                'cancelled_at',
                'delivered_at',
                'attended_at',
                'requisition_printed_at',
                'include_shipping',
                'folio_end',
                'folio_start',
            ]);
        });

        DB::table('label_requests')
            ->whereNull('label_part_number')
            ->update(['label_part_number' => '']);

        Schema::table('label_requests', function (Blueprint $table) {
            $table->string('label_part_number', 80)->nullable(false)->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE label_requests MODIFY COLUMN status ENUM('requested','in_progress','completed','cancelled') NOT NULL DEFAULT 'requested'"
            );
        }
    }
};
