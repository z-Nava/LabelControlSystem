<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterAssemblyClassificationRule extends Model
{
    public const FIELD_ASSEMBLY = 'assembly';

    public const FIELD_LINE = 'line';

    public const MATCH_FIELDS = [
        self::FIELD_ASSEMBLY,
        self::FIELD_LINE,
    ];

    protected $fillable = [
        'match_field',
        'prefix',
        'description',
        'allows_assembly',
        'allows_packaging',
        'active',
    ];

    protected $casts = [
        'allows_assembly' => 'boolean',
        'allows_packaging' => 'boolean',
        'active' => 'boolean',
    ];

    public static function fieldLabel(string $field): string
    {
        return match ($field) {
            self::FIELD_ASSEMBLY => 'Assembly',
            self::FIELD_LINE => 'Línea',
            default => ucfirst($field),
        };
    }
}
