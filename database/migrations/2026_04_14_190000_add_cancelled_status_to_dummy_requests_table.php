<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE dummy_requests MODIFY COLUMN status ENUM('requested','in_progress','completed','cancelled') NOT NULL DEFAULT 'requested'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE dummy_requests MODIFY COLUMN status ENUM('requested','in_progress','completed') NOT NULL DEFAULT 'requested'");
    }
};
