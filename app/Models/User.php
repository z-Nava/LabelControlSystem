<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Shift;

class User extends Authenticatable
{

    public const AVAILABLE_MODULE_PERMISSIONS = [
        'master',
        'labels',
        'dummy',
        'oracle',
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
        return $this->roles()->where('name', $roleName)->exists();
    }


    public function hasModuleAccess(string $module): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        if (!$this->hasRole('label_room')) {
            return false;
        }

        $permissions = $this->module_permissions;

        if (empty($permissions)) {
            return true;
        }

        return in_array($module, $permissions, true);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function getShiftLabelAttribute(): ?string
    {
        if (!$this->shift) {
            return null;
        }

        return 'Shift ' . $this->shift->code;
    }

}
