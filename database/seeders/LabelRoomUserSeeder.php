<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LabelRoomUserSeeder extends Seeder
{
    public function run(): void
    {
        $shiftB = Shift::where('code', 'B')->first();

        // Crear o actualizar usuario de Label Room
        $user = User::updateOrCreate(
            ['employee_no' => '20000'], // ← número de empleado del operador
            [
                'name'      => 'Operador Label Room',
                // Password vacío (no se usa para label_room)
                // Aun así ponemos uno dummy para cumplir con la columna
                'password'  => Hash::make(str()->random(32)),
                'is_active' => true,
                'shift_id'  => $shiftB ? $shiftB->id : null, // asignar turno B por defecto
            ]
        );

        // Asignar rol label_room
        $labelRoomRole = Role::where('name', 'label_room')->first();

        if ($labelRoomRole) {
            $user->roles()->syncWithoutDetaching([$labelRoomRole->id]);
        }
    }
}
