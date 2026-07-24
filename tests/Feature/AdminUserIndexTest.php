<?php

use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('the admin user index separates users into role tables', function () {
    $adminRole = Role::query()->create([
        'name' => 'admin',
        'description' => 'Administrador',
    ]);
    $labelRoomRole = Role::query()->create([
        'name' => 'label_room',
        'description' => 'Label Room',
    ]);
    $kioskRole = Role::query()->where('name', 'kiosk')->firstOrFail();

    $shift = Shift::query()->create([
        'code' => 'A',
        'name' => 'Turno A',
        'active' => true,
    ]);
    $line = ProductionLine::query()->create([
        'code' => 'LINE-01',
        'name' => 'Línea de prueba',
        'line_type' => 'EMPAQUE',
        'active' => true,
    ]);

    $createUser = function (string $employeeNo, string $name, array $roles, array $profile = []): User {
        $user = User::query()->create([
            'employee_no' => $employeeNo,
            'name' => $name,
            'password' => Hash::make('password'),
            'is_active' => true,
            ...$profile,
        ]);
        $user->roles()->attach($roles);

        return $user;
    };

    $admin = $createUser('41001', 'Usuario Admin', [$adminRole->id]);
    $createUser('41002', 'Usuario Label Room', [$labelRoomRole->id]);
    $createUser('41003', 'Usuario Kiosk', [$kioskRole->id], [
        'shift_id' => $shift->id,
        'production_line_id' => $line->id,
        'position' => 'operator',
    ]);
    $createUser('41004', 'Usuario Híbrido', [$labelRoomRole->id, $kioskRole->id], [
        'shift_id' => $shift->id,
        'production_line_id' => $line->id,
        'position' => 'leader',
    ]);
    $createUser('41005', 'Usuario Sin Rol', []);

    $response = $this
        ->actingAs($admin)
        ->withSession(['auth_access_mode' => 'admin'])
        ->get(route('users.index'));

    $response
        ->assertOk()
        ->assertSee('Administradores')
        ->assertSee('Label Room')
        ->assertSee('Kiosk · Producción')
        ->assertViewHas('adminUsers', function ($users): bool {
            return $users->getCollection()->pluck('employee_no')->all() === ['41001'];
        })
        ->assertViewHas('labelRoomUsers', function ($users): bool {
            return $users->getCollection()->pluck('employee_no')->sort()->values()->all() === ['41002', '41004'];
        })
        ->assertViewHas('kioskUsers', function ($users): bool {
            return $users->getCollection()->pluck('employee_no')->sort()->values()->all() === ['41003', '41004'];
        });
});
