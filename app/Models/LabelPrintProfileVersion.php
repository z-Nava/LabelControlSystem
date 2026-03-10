<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelPrintProfileVersion extends Model
{
    protected $table = 'label_print_profile_versions';

    protected $fillable = [
        'label_print_profile_id',
        'version',
        'snapshot',
        'created_by_user_id',
    ];

    protected $casts = [
        'label_print_profile_id' => 'integer',
        'version' => 'integer',
        'snapshot' => 'array',
        'created_by_user_id' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(LabelPrintProfile::class, 'label_print_profile_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}