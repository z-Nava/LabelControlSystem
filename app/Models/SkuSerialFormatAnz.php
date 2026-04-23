<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuSerialFormatAnz extends Model
{
    protected $table = 'sku_serial_format_anz';

    protected $fillable = [
        'sku_serial_format_id',
        'product_prefix',
        'product_prefix_length',
        'tool_version_letter',
        'tool_version_required',
        'customer_tool_code',
        'customer_tool_code_required',
        'unit_digits',
        'qr_separator',
        'include_customer_tool_code_in_qr',
        'print_format',
        'reset_scope',
        'pattern',
        'qr_pattern',
    ];

    protected $casts = [
        'product_prefix_length' => 'integer',
        'tool_version_required' => 'boolean',
        'customer_tool_code_required' => 'boolean',
        'unit_digits' => 'integer',
        'include_customer_tool_code_in_qr' => 'boolean',
    ];

    public function serialFormat(): BelongsTo
    {
        return $this->belongsTo(SkuSerialFormat::class, 'sku_serial_format_id');
    }
}
