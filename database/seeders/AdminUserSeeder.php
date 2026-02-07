<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $shiftA = Shift::where('code', 'A')->first();

        // Crear o actualizar usuario admin
        $admin = User::updateOrCreate(
            ['employee_no' => '14781'], // <-- cambia si quieres otro número
            [
                'name'       => 'Administrador',
                'password'   => Hash::make('admin123'), // luego lo cambias
                'is_active'  => true,
                'shift_id'   => $shiftA ? $shiftA->id : null, // asignar turno A por defecto
            ]
        );

        // Asignar rol admin
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
