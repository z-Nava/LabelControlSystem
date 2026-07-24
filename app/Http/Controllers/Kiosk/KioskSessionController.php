<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kiosk\KioskLoginRequest;
use App\Http\Requests\Kiosk\StoreKioskProfileRequest;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KioskSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('kiosk_user_id')) {
            return redirect()->route('kiosk.dashboard');
        }

        return view('auth.kiosk-login');
    }

    public function store(KioskLoginRequest $request): RedirectResponse
    {
        $employeeNo = $request->string('employee_no')->toString();
        $user = User::query()
            ->with('roles')
            ->where('employee_no', $employeeNo)
            ->first();

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'employee_no' => 'Este perfil está inactivo. Contacta a un administrador.',
            ]);
        }

        $this->resetSession($request);

        if (! $user || ! $user->isRegisteredForKiosk()) {
            $request->session()->put('kiosk_pending_employee_no', $employeeNo);

            return redirect()->route('kiosk.register');
        }

        $this->startKioskSession($request, $user);

        return redirect()->route('kiosk.dashboard');
    }

    public function createRegistration(Request $request): View|RedirectResponse
    {
        $employeeNo = $this->pendingEmployeeNo($request);

        if ($employeeNo === null) {
            return redirect()->route('kiosk.login');
        }

        $user = User::query()->where('employee_no', $employeeNo)->first();

        if ($user && ! $user->is_active) {
            $request->session()->forget('kiosk_pending_employee_no');

            return redirect()->route('kiosk.login')->withErrors([
                'employee_no' => 'Este perfil está inactivo. Contacta a un administrador.',
            ]);
        }

        return view('auth.kiosk-register', [
            'employeeNo' => $employeeNo,
            'user' => $user,
            'productionLines' => ProductionLine::query()
                ->where('active', true)
                ->orderBy('line_type')
                ->orderBy('code')
                ->get(),
            'shifts' => Shift::query()->where('active', true)->orderBy('code')->get(),
            'positions' => User::PRODUCTION_POSITIONS,
        ]);
    }

    public function storeRegistration(StoreKioskProfileRequest $request): RedirectResponse
    {
        $employeeNo = $this->pendingEmployeeNo($request);

        if ($employeeNo === null) {
            return redirect()->route('kiosk.login')->withErrors([
                'employee_no' => 'Escanea nuevamente tu número de empleado.',
            ]);
        }

        $data = $request->validated();

        $user = DB::transaction(function () use ($employeeNo, $data): User {
            $user = User::query()
                ->where('employee_no', $employeeNo)
                ->lockForUpdate()
                ->first();

            if ($user && ! $user->is_active) {
                throw ValidationException::withMessages([
                    'employee_no' => 'Este perfil está inactivo. Contacta a un administrador.',
                ]);
            }

            $profile = [
                'name' => $data['name'],
                'production_line_id' => $data['production_line_id'],
                'shift_id' => $data['shift_id'],
                'position' => $data['position'],
            ];

            if ($user) {
                $user->update($profile);
            } else {
                $user = User::query()->create([
                    'employee_no' => $employeeNo,
                    ...$profile,
                    'password' => Hash::make(Str::random(64)),
                    'is_active' => true,
                ]);
            }

            $kioskRole = Role::query()->where('name', 'kiosk')->first();

            if (! $kioskRole) {
                throw (new ModelNotFoundException)->setModel(Role::class, ['kiosk']);
            }

            $user->roles()->syncWithoutDetaching($kioskRole);

            return $user->load(['productionLine', 'roles', 'shift']);
        }, attempts: 3);

        $this->resetSession($request);
        $this->startKioskSession($request, $user);

        return redirect()
            ->route('kiosk.dashboard')
            ->with('success', 'Tu perfil quedó registrado correctamente.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->resetSession($request);

        return redirect()->route('kiosk.login');
    }

    private function pendingEmployeeNo(Request $request): ?string
    {
        $employeeNo = $request->session()->get('kiosk_pending_employee_no');

        return is_string($employeeNo) && preg_match('/^\d{3,5}$/', $employeeNo)
            ? $employeeNo
            : null;
    }

    private function startKioskSession(Request $request, User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();

        $request->session()->put([
            'kiosk_user_id' => $user->id,
            'kiosk_employee_no' => $user->employee_no,
        ]);
    }

    private function resetSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
