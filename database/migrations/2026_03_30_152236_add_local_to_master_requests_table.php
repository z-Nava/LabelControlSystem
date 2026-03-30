<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->string('local', 20)->nullable()->after('destination');
            $table->index('local');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_requests', function (Blueprint $table) {
            $table->dropIndex(['local']);
            $table->dropColumn('local');
        });
    }
};
