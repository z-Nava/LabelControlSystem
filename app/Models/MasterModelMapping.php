<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterModelMapping extends Model
{
    public const TYPE_ASSEMBLY = 'assembly';
    public const TYPE_ASSEMBLY_PACKAGING = 'assembly_packaging';
    public const TYPE_BATTERIES_ASSEMBLY = 'batteries_assembly';
    public const TYPE_MOTORS_MOLDING = 'motors_molding';

    public const TYPES = [
        self::TYPE_ASSEMBLY,
        self::TYPE_ASSEMBLY_PACKAGING,
        self::TYPE_BATTERIES_ASSEMBLY,
        self::TYPE_MOTORS_MOLDING,
    ];

    protected $fillable = [
        'np',
        'sku',
        'master_sheet_type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public static function labelForType(string $type): string
    {
        return match ($type) {
            self::TYPE_ASSEMBLY => 'ENSAMBLE',
            self::TYPE_ASSEMBLY_PACKAGING => 'ENSAMBLE - EMPAQUE',
            self::TYPE_BATTERIES_ASSEMBLY => 'BATERÍAS',
            self::TYPE_MOTORS_MOLDING => 'MOTORES - MOLDEO',
            default => strtoupper($type),
        };
    }
}
