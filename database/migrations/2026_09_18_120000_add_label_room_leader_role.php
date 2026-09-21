<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'label_room_leader')->value('id');

        if ($roleId === null) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'label_room_leader',
                'description' => 'Líder de cuarto de etiquetas; requiere también el rol label_room',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')
            ->whereNotNull('module_permissions')
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($roleId): void {
                foreach ($users as $user) {
                    $permissions = json_decode($user->module_permissions, true);

                    if (! is_array($permissions) || ! in_array('label_room_leader', $permissions, true)) {
                        continue;
                    }

                    DB::table('role_user')->insertOrIgnore([
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                    ]);

                    DB::table('users')->where('id', $user->id)->update([
                        'module_permissions' => json_encode(array_values(array_diff($permissions, ['label_room_leader']))),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Keep assigned roles and user permissions intact on rollback.
    }
};
