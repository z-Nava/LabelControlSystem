<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('dummy_requests', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('dummy_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
