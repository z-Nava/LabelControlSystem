<?php

namespace App\Models;

use App\Support\SerialStandards;
use Illuminate\Database\Eloquent\Model;

class SkuSerialFormat extends Model
{
    protected $table = 'sku_serial_formats';

    protected $fillable = [
        'sku',
        'serial_standard',
        'serial_scheme',
        'description',
        'serial_length',
        'qr_payload_format',
        'date_mode',
        'month_letter_enabled',
        'month_letter_map',
        'ul_prefix',
        'ul_prefix_length',
        'ul_serial_break',
        'ul_plant_code',
        'ul_use_plant_code',
        'emea_prefix',
        'emea_prefix_source',
        'emea_prefix_digits',
        'emea_conformity_code',
        'emea_plant_code',
        'emea_unit_digits',
        'emea_declaration_required',
        'anz_customer_tool_code',
        'anz_product_prefix',
        'anz_tool_version',
        'anz_tool_version_required',
        'anz_unit_digits',
        'anz_qr_separator',
        'anz_include_customer_tool_code_in_qr',
        'anz_serial_print_format',
        'separator',
        'year_digits',
        'week_digits',
        'include_year',
        'include_week',
        'pattern',
        'unit_length',
        'unit_digits',
        'next_unit',
        'is_active',
        'updated_by_user_id',
    ];

    protected $casts = [
        'serial_length' => 'integer',
        'emea_prefix_digits' => 'integer',
        'emea_unit_digits' => 'integer',
        'anz_unit_digits' => 'integer',
        'ul_prefix_length' => 'integer',
        'unit_length' => 'integer',
        'unit_digits' => 'integer',
        'year_digits' => 'integer',
        'week_digits' => 'integer',
        'next_unit' => 'integer',
        'include_year' => 'boolean',
        'include_week' => 'boolean',
        'month_letter_enabled' => 'boolean',
        'ul_use_plant_code' => 'boolean',
        'emea_declaration_required' => 'boolean',
        'anz_tool_version_required' => 'boolean',
        'anz_include_customer_tool_code_in_qr' => 'boolean',
        'is_active' => 'boolean',
        'updated_by_user_id' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isEmea(): bool
    {
        return strtoupper((string) $this->serial_standard) === SerialStandards::EMEA;
    }

    public function isAnz(): bool
    {
        return strtoupper((string) $this->serial_standard) === SerialStandards::ANZ;
    }

    public function isInternational(): bool
    {
        return SerialStandards::isInternational((string) $this->serial_standard);
    }

    public function componentPrefix(): string
    {
        if ($this->isAnz()) {
            return (string) ($this->anz_product_prefix ?: $this->emea_prefix);
        }

        return (string) ($this->isInternational() ? $this->emea_prefix : $this->ul_prefix);
    }

    public function componentBreak(): string
    {
        if ($this->isAnz()) {
            return (string) ($this->anz_tool_version ?: $this->emea_conformity_code);
        }

        return (string) ($this->isInternational() ? $this->emea_conformity_code : $this->ul_serial_break);
    }

    public function componentPlantCode(): string
    {
        if (!$this->isInternational()) {
            return (bool) ($this->ul_use_plant_code ?? true) ? (string) $this->ul_plant_code : '';
        }

        return $this->isAnz() ? '' : (string) $this->emea_plant_code;
    }

    public function anzQrCustomerToolCode(): string
    {
        return strtoupper(trim((string) $this->anz_customer_tool_code));
    }

    public function shouldIncludeAnzCustomerToolCodeInQr(): bool
    {
        return $this->isAnz() && (bool) ($this->anz_include_customer_tool_code_in_qr ?? true);
    }

    public function effectiveUnitDigits(): int
    {
        if ($this->isAnz() && $this->anz_unit_digits) {
            return (int) $this->anz_unit_digits;
        }

        if ($this->isEmea() && $this->emea_unit_digits) {
            return (int) $this->emea_unit_digits;
        }

        return (int) ($this->unit_digits ?: $this->unit_length ?: 5);
    }
}
