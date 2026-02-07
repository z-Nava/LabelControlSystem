<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear o actualizar usuario admin
        $admin = User::updateOrCreate(
            ['employee_no' => '14781'], // <-- cambia si quieres otro número
            [
                'name'       => 'Administrador',
                'password'   => Hash::make('admin123'), // luego lo cambias
                'is_active'  => true,
            ]
        );

        // Asignar rol admin
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
