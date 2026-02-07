<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        Shift::updateOrCreate(
            ['code' => 'A'],
            ['name' => 'Turno A', 'active' => true]
        );

        Shift::updateOrCreate(
            ['code' => 'B'],
            ['name' => 'Turno B', 'active' => true]
        );

        Shift::updateOrCreate(
            ['code' => 'C'],
            ['name' => 'Turno C', 'active' => true]
        );
    }
}
