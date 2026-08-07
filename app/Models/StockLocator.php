<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLocator extends Model
{
    protected $fillable = [
        'oracle_line',
        'subinventory',
        'stock_locator',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
