<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingAssemblyMapping extends Model
{
    protected $fillable = [
        'rating_part_number',
        'serial_part_number',
        'shipping_part_number',
        'inner_part_number',
        'assembly_part_number',
        'market',
        'active',
        'updated_by_user_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'updated_by_user_id' => 'integer',
    ];

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
