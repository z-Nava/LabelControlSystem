<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const AVAILABLE_MODULE_PERMISSIONS = [
        'master',
        'labels',
        'dummy',
        'oracle',
    ];

    public const PRODUCTION_POSITIONS = [
        'operator' => 'Operador',
        'utility' => 'Utility',
        'leader' => 'Líder',
    ];

    use Notifiable;

    protected $fillable = [
        'employee_no',
        'name',
        'password',
        'is_active',
        'module_permissions',
        'last_login_at',
        'shift_id',
        'production_line_id',
        'position',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'module_permissions' => 'array',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string $roleName): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', $roleName);
        }

        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasModuleAccess(string $module): bool
    {
        if ($this->hasRole('admin') && session('auth_access_mode') === 'admin') {
            return true;
        }

        if (! $this->hasRole('label_room')) {
            return false;
        }

        $permissions = $this->module_permissions;

        if (empty($permissions)) {
            return true;
        }

        return in_array($module, $permissions, true);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function hasCompleteKioskProfile(): bool
    {
        return filled($this->name)
            && $this->shift_id !== null
            && $this->production_line_id !== null
            && array_key_exists((string) $this->position, self::PRODUCTION_POSITIONS);
    }

    public function isRegisteredForKiosk(): bool
    {
        return $this->hasRole('kiosk') && $this->hasCompleteKioskProfile();
    }

    public function getShiftLabelAttribute(): ?string
    {
        if (! $this->shift) {
            return null;
        }

        return 'Shift '.$this->shift->code;
    }

    public function getPositionLabelAttribute(): ?string
    {
        return self::PRODUCTION_POSITIONS[$this->position] ?? null;
    }
}
