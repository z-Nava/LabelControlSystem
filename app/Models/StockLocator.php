<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLocator extends Model
{
    protected $fillable = [
        'stock_locator',
        'subinventory',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
