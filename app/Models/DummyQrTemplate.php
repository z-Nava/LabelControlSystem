<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DummyQrTemplate extends Model
{
    protected $table = 'dummy_qr_templates';

    protected $fillable = [
        'name',
        'dummy_type',
        'dpi',
        'width_mm',
        'height_mm',
        'qr_x',
        'qr_y',
        'qr_magnification',
        'qr_orientation',
        'fg_x',
        'fg_y',
        'fg_font_size',
        'job_x',
        'job_y',
        'job_font_size',
        'consecutive_x',
        'consecutive_y',
        'consecutive_font_size',
        'title_x',
        'title_y',
        'title_font_size',
        'connection_type',
        'default_printer_name',
        'default_printer_ip',
        'zpl',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'dpi' => 'integer',
        'width_mm' => 'decimal:2',
        'height_mm' => 'decimal:2',
        'qr_x' => 'integer',
        'qr_y' => 'integer',
        'qr_magnification' => 'integer',
        'fg_x' => 'integer',
        'fg_y' => 'integer',
        'fg_font_size' => 'integer',
        'job_x' => 'integer',
        'job_y' => 'integer',
        'job_font_size' => 'integer',
        'consecutive_x' => 'integer',
        'consecutive_y' => 'integer',
        'consecutive_font_size' => 'integer',
        'title_x' => 'integer',
        'title_y' => 'integer',
        'title_font_size' => 'integer',
        'is_active' => 'boolean',
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
