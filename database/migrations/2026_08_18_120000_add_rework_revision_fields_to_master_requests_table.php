<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->foreignId('parent_master_request_id')
                ->nullable()
                ->after('id')
                ->constrained('master_requests')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('revision_number')->default(0)->after('parent_master_request_id');
            $table->string('model', 120)->nullable()->after('local');
            $table->text('rework_reason')->nullable()->after('status');
            $table->foreignId('reworked_by_user_id')
                ->nullable()
                ->after('rework_reason')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('reworked_by_name', 120)->nullable()->after('reworked_by_user_id');
            $table->dateTime('reworked_at')->nullable()->after('reworked_by_name');
            $table->json('rework_changes')->nullable()->after('reworked_at');

            $table->unique(
                ['parent_master_request_id', 'revision_number'],
                'master_requests_parent_revision_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->dropUnique('master_requests_parent_revision_unique');
            $table->dropConstrainedForeignId('reworked_by_user_id');
            $table->dropConstrainedForeignId('parent_master_request_id');
            $table->dropColumn([
                'revision_number',
                'model',
                'rework_reason',
                'reworked_by_name',
                'reworked_at',
                'rework_changes',
            ]);
        });
    }
};
