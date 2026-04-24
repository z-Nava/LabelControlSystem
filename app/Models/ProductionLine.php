<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
    public const TYPES = [
        'CONSOLAS',
        'BATERIAS',
        'HIDRAULICOS',
        'EMPAQUE',
        'MOTORES ROTOR',
        'MOTORES STATOR',
        'MX FUEL',
    ];

    protected $fillable = [
        'code',
        'name',
        'line_type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
