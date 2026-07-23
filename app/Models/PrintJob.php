<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintJob extends Model
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'payload',
        'order_detail_ids',
        'status',
        'attempts',
        'error',
        'locked_at',
        'printed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'order_detail_ids' => 'array',
        'locked_at' => 'datetime',
        'printed_at' => 'datetime',
    ];
}
