<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('label_requests', function (Blueprint $table) {
            $table->string('request_kind', 20)
                ->default('standard')
                ->after('id');

            $table->index('request_kind', 'idx_label_requests_request_kind');
        });
    }

    public function down(): void
    {
        Schema::table('label_requests', function (Blueprint $table) {
            $table->dropIndex('idx_label_requests_request_kind');
            $table->dropColumn('request_kind');
        });
    }
};
