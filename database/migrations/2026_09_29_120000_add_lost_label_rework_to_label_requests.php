<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('label_requests', function (Blueprint $table): void {
            $table->foreignId('source_label_request_id')->nullable()->constrained('label_requests')->restrictOnDelete();
            $table->text('rework_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('label_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('source_label_request_id');
            $table->dropColumn('rework_reason');
        });
    }
};
