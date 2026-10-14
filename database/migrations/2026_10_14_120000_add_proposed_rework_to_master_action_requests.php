<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_action_requests', function (Blueprint $table) {
            $table->json('proposed_rework')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('master_action_requests', function (Blueprint $table) {
            $table->dropColumn('proposed_rework');
        });
    }
};
