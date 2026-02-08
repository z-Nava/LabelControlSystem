<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLine extends Model
{
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
