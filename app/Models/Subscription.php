<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscriber_email',
        'subscription_date',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'subscription_date' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
