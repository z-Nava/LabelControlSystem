<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $search = request('q');

        $users = User::query()
            ->with(['roles', 'shift'])
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('employee_no', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::orderBy('name')->get(),
            'shifts' => Shift::orderBy('code')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'employee_no' => $data['employee_no'],
            'name' => $data['name'],
            'password' => Hash::make($data['password'] ?? Str::random(32)),
            'shift_id' => $data['shift_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->roles()->sync($data['roles']);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'shifts' => Shift::orderBy('code')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $payload = [
            'employee_no' => $data['employee_no'],
            'name' => $data['name'],
            'shift_id' => $data['shift_id'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ];

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->roles()->sync($data['roles']);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggle(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return redirect()->route('users.index')->with('success', 'Estado actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
