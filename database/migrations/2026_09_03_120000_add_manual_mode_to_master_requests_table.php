<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->foreignId('line_id')->nullable()->change();
            $table->boolean('is_manual')->default(false)->after('request_source');
            $table->text('manual_reason')->nullable()->after('is_manual');
            $table->index('is_manual');
        });
    }

    public function down(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->dropIndex(['is_manual']);
            $table->dropColumn(['is_manual', 'manual_reason']);
            $table->foreignId('line_id')->nullable(false)->change();
        });
    }
};
