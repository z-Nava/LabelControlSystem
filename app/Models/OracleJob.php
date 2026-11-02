<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OracleJob extends Model
{
    public function shippingDestination(): ?string
    {
        foreach ([$this->ship_code, $this->ship_to] as $value) {
            $destination = strtoupper(trim((string) $value));

            if ($destination !== '') {
                return $destination;
            }
        }

        return null;
    }

    protected $fillable = [
        'job_number',
        'line',
        'job_status',
        'assembly',
        'bom_revision',
        'part_description',
        'job_qty',
        'qty_completed',
        'quantity_remainder',
        'scheduled_start_date',
        'last_update_date',
        'job_description',
        'ttl_cust_po',
        'ship_to',
        'ship_code',
        'ship_address',
        'source_file_name',
        'imported_at',
    ];

    protected $casts = [
        'scheduled_start_date' => 'datetime',
        'last_update_date' => 'datetime',
        'imported_at' => 'datetime',
    ];
}
