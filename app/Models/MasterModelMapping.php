<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterModelMapping extends Model
{
    public const TYPE_ASSEMBLY = 'assembly';

    public const TYPE_ORT_ASSEMBLY = 'ort_assembly';

    public const TYPE_ASSEMBLY_PACKAGING = 'assembly_packaging';

    public const TYPE_BATTERIES_ASSEMBLY = 'batteries_assembly';

    public const TYPE_MOTORS_MOLDING = 'motors_molding';

    public const ORT_DEFAULT_SUBINVENTORY = 'QUARANTINE';

    public const ORT_DEFAULT_LOCAL = 'RELIABILITY';

    public const ALTERNATE_STOCK_LOCATOR = 'SMARKET-1';

    public const TYPES = [
        self::TYPE_ASSEMBLY,
        self::TYPE_ASSEMBLY_PACKAGING,
        self::TYPE_BATTERIES_ASSEMBLY,
        self::TYPE_MOTORS_MOLDING,
    ];

    public const REQUEST_TYPES = [
        self::TYPE_ASSEMBLY,
        self::TYPE_ORT_ASSEMBLY,
        self::TYPE_ASSEMBLY_PACKAGING,
        self::TYPE_BATTERIES_ASSEMBLY,
        self::TYPE_MOTORS_MOLDING,
    ];

    public const TYPES_BY_LINE_TYPE = [
        ProductionLine::TYPE_BATTERIES => [
            self::TYPE_BATTERIES_ASSEMBLY,
            self::TYPE_ASSEMBLY_PACKAGING,
        ],
        ProductionLine::TYPE_CONSOLES => [
            self::TYPE_ASSEMBLY,
            self::TYPE_ORT_ASSEMBLY,
            self::TYPE_ASSEMBLY_PACKAGING,
        ],
        ProductionLine::TYPE_PACKAGING => [
            self::TYPE_ASSEMBLY,
            self::TYPE_ORT_ASSEMBLY,
            self::TYPE_ASSEMBLY_PACKAGING,
        ],
        ProductionLine::TYPE_HYDRAULICS => [
            self::TYPE_ASSEMBLY,
            self::TYPE_ORT_ASSEMBLY,
            self::TYPE_ASSEMBLY_PACKAGING,
        ],
        ProductionLine::TYPE_MX_FUEL => [
            self::TYPE_ASSEMBLY,
            self::TYPE_ORT_ASSEMBLY,
            self::TYPE_ASSEMBLY_PACKAGING,
        ],
        ProductionLine::TYPE_MOTORS => [
            self::TYPE_MOTORS_MOLDING,
        ],
        ProductionLine::TYPE_SWITCH_LINE => [
            self::TYPE_ASSEMBLY,
            self::TYPE_ASSEMBLY_PACKAGING,
        ],
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
            self::TYPE_ORT_ASSEMBLY => 'ENSAMBLE ORT',
            self::TYPE_ASSEMBLY_PACKAGING => 'ENSAMBLE - EMPAQUE',
            self::TYPE_BATTERIES_ASSEMBLY => 'BATERÍAS',
            self::TYPE_MOTORS_MOLDING => 'MOTORES - MOLDEO',
            default => strtoupper($type),
        };
    }

    public static function requestLabelForType(string $type): string
    {
        return match ($type) {
            self::TYPE_ASSEMBLY => 'HOJA MASTER - ENSAMBLE',
            self::TYPE_ORT_ASSEMBLY => 'HOJA MASTER - ENSAMBLE ORT',
            self::TYPE_ASSEMBLY_PACKAGING => 'HOJA MASTER ENSAMBLE - EMPAQUE',
            self::TYPE_BATTERIES_ASSEMBLY => 'HOJA MASTER - ENSAMBLE BATERÍAS',
            self::TYPE_MOTORS_MOLDING => 'HOJA MASTER - MOTORES - MOLDEO',
            default => strtoupper($type),
        };
    }

    public static function allowedTypesForLineType(string $lineType): array
    {
        return self::TYPES_BY_LINE_TYPE[strtoupper(trim($lineType))] ?? [];
    }

    public static function isAllowedForLineType(string $requestType, string $lineType): bool
    {
        return in_array($requestType, self::allowedTypesForLineType($lineType), true);
    }

    public static function requestOptions(): array
    {
        $options = [];

        foreach (self::REQUEST_TYPES as $requestType) {
            $lineTypes = [];

            foreach (self::TYPES_BY_LINE_TYPE as $lineType => $allowedTypes) {
                if (in_array($requestType, $allowedTypes, true)) {
                    $lineTypes[] = $lineType;
                }
            }

            $options[$requestType] = [
                'label' => self::requestLabelForType($requestType),
                'line_types' => $lineTypes,
            ];
        }

        return $options;
    }

    public static function ortAssemblyRequestConfiguration(): array
    {
        return [
            'type' => self::TYPE_ORT_ASSEMBLY,
            'default_local' => self::ORT_DEFAULT_LOCAL,
            'default_subinventory' => self::ORT_DEFAULT_SUBINVENTORY,
        ];
    }
}
