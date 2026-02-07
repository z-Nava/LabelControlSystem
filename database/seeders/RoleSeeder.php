<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrador del sistema']
        );

        Role::updateOrCreate(
            ['name' => 'label_room'],
            ['description' => 'Personal de cuarto de etiquetas']
        );
    }
}
