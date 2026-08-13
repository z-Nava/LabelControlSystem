<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
    public const TYPE_CONSOLES = 'CONSOLAS';

    public const TYPE_BATTERIES = 'BATERIAS';

    public const TYPE_HYDRAULICS = 'HIDRAULICOS';

    public const TYPE_PACKAGING = 'EMPAQUE';

    public const TYPE_MOTORS = 'MOTORES';

    public const TYPE_MX_FUEL = 'MX FUEL';

    public const TYPE_SWITCH_LINE = 'SWITCH LINE';

    public const TYPES = [
        self::TYPE_CONSOLES,
        self::TYPE_BATTERIES,
        self::TYPE_HYDRAULICS,
        self::TYPE_PACKAGING,
        self::TYPE_MOTORS,
        self::TYPE_MX_FUEL,
        self::TYPE_SWITCH_LINE,
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
