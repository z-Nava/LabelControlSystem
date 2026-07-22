<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'kiosk'],
            [
                'description' => 'Personal de Producción con acceso al kiosko de requisiciones',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'kiosk')->delete();
    }
};
