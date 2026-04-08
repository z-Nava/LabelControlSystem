<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuSerialFormat extends Model
{
    protected $table = 'sku_serial_formats';

    protected $fillable = [
        'sku',
        'serial_standard',
        'serial_scheme',
        'ul_prefix',
        'ul_serial_break',
        'ul_plant_code',
        'emea_prefix',
        'emea_conformity_code',
        'emea_plant_code',
        'separator',
        'year_digits',
        'week_digits',
        'include_year',
        'include_week',
        'pattern',
        'unit_length',
        'next_unit',
        'is_active',
        'updated_by_user_id',
    ];

    protected $casts = [
        'unit_length' => 'integer',
        'year_digits' => 'integer',
        'week_digits' => 'integer',
        'next_unit' => 'integer',
        'include_year' => 'boolean',
        'include_week' => 'boolean',
        'is_active' => 'boolean',
        'updated_by_user_id' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isEmea(): bool
    {
        return strtoupper((string) $this->serial_standard) === 'EMEA';
    }

    public function componentPrefix(): string
    {
        return (string) ($this->isEmea() ? $this->emea_prefix : $this->ul_prefix);
    }

    public function componentBreak(): string
    {
        return (string) ($this->isEmea() ? $this->emea_conformity_code : $this->ul_serial_break);
    }

    public function componentPlantCode(): string
    {
        return (string) ($this->isEmea() ? $this->emea_plant_code : $this->ul_plant_code);
    }
}
