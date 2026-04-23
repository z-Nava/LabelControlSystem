<?php

namespace App\Models;

use App\Support\SerialStandards;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SkuSerialFormat extends Model
{
    private const STANDARD_BY_SCHEME = [
        'ul_standard' => SerialStandards::UL,
        'emea_rating' => SerialStandards::EMEA,
        'anz_standard' => SerialStandards::ANZ,
    ];

    protected $table = 'sku_serial_formats';

    protected $fillable = [
        'sku',
        'market',
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

    public function ulConfig(): HasOne
    {
        return $this->hasOne(SkuSerialFormatUl::class, 'sku_serial_format_id');
    }

    public function emeaConfig(): HasOne
    {
        return $this->hasOne(SkuSerialFormatEmea::class, 'sku_serial_format_id');
    }

    public function anzConfig(): HasOne
    {
        return $this->hasOne(SkuSerialFormatAnz::class, 'sku_serial_format_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isEmea(): bool
    {
        return $this->resolvedSerialStandard() === SerialStandards::EMEA;
    }

    public function isAnz(): bool
    {
        return $this->resolvedSerialStandard() === SerialStandards::ANZ;
    }

    public function isInternational(): bool
    {
        return SerialStandards::isInternational($this->resolvedSerialStandard());
    }

    public function resolvedSerialStandard(): string
    {
        $scheme = strtolower(trim((string) $this->serial_scheme));
        if (isset(self::STANDARD_BY_SCHEME[$scheme])) {
            return self::STANDARD_BY_SCHEME[$scheme];
        }

        $market = strtoupper(trim((string) $this->market));
        if (in_array($market, SerialStandards::all(), true)) {
            return $market;
        }

        return SerialStandards::normalize((string) $this->serial_standard);
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

    public function getUlPrefixAttribute($value): ?string
    {
        return $this->ulConfig?->prefix ?? $value;
    }

    public function getUlPrefixLengthAttribute($value): ?int
    {
        return $this->ulConfig?->prefix_length ?? $value;
    }

    public function getUlSerialBreakAttribute($value): ?string
    {
        return $this->ulConfig?->serial_break ?? $value;
    }

    public function getUlPlantCodeAttribute($value): ?string
    {
        return $this->ulConfig?->plant_code ?? $value;
    }

    public function getUlUsePlantCodeAttribute($value): bool
    {
        if ($this->ulConfig) {
            return (bool) $this->ulConfig->use_plant_code;
        }

        return (bool) $value;
    }

    public function getEmeaPrefixAttribute($value): ?string
    {
        return $this->emeaConfig?->prefix_value ?? $value;
    }

    public function getEmeaPrefixSourceAttribute($value): ?string
    {
        return $this->emeaConfig?->prefix_source ?? $value;
    }

    public function getEmeaPrefixDigitsAttribute($value): ?int
    {
        return $this->emeaConfig?->prefix_digits ?? $value;
    }

    public function getEmeaConformityCodeAttribute($value): ?string
    {
        return $this->emeaConfig?->conformity_code ?? $value;
    }

    public function getEmeaPlantCodeAttribute($value): ?string
    {
        return $this->emeaConfig?->plant_code ?? $value;
    }

    public function getEmeaUnitDigitsAttribute($value): ?int
    {
        return $this->emeaConfig?->unit_digits ?? $value;
    }

    public function getEmeaDeclarationRequiredAttribute($value): bool
    {
        if ($this->emeaConfig) {
            return (bool) $this->emeaConfig->declaration_required;
        }

        return (bool) $value;
    }

    public function getAnzProductPrefixAttribute($value): ?string
    {
        return $this->anzConfig?->product_prefix ?? $value;
    }

    public function getAnzToolVersionAttribute($value): ?string
    {
        return $this->anzConfig?->tool_version_letter ?? $value;
    }

    public function getAnzToolVersionRequiredAttribute($value): bool
    {
        if ($this->anzConfig) {
            return (bool) $this->anzConfig->tool_version_required;
        }

        return (bool) $value;
    }

    public function getAnzCustomerToolCodeAttribute($value): ?string
    {
        return $this->anzConfig?->customer_tool_code ?? $value;
    }

    public function getAnzUnitDigitsAttribute($value): ?int
    {
        return $this->anzConfig?->unit_digits ?? $value;
    }

    public function getAnzQrSeparatorAttribute($value): ?string
    {
        return $this->anzConfig?->qr_separator ?? $value;
    }

    public function getAnzIncludeCustomerToolCodeInQrAttribute($value): bool
    {
        if ($this->anzConfig) {
            return (bool) $this->anzConfig->include_customer_tool_code_in_qr;
        }

        return (bool) $value;
    }

    public function getAnzSerialPrintFormatAttribute($value): ?string
    {
        return $this->anzConfig?->print_format ?? $value;
    }

    public function getResetScopeAttribute(): string
    {
        if ($this->isAnz()) {
            return (string) ($this->anzConfig?->reset_scope ?: 'monthly');
        }

        if ($this->isEmea()) {
            return (string) ($this->emeaConfig?->reset_scope ?: 'monthly');
        }

        return (string) ($this->ulConfig?->reset_scope ?: 'weekly');
    }
}
