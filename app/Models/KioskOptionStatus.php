<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KioskOptionStatus extends Model
{
    protected $primaryKey = 'option_key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['option_key', 'status'];
}
