<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreparationArea extends Model
{
    use HasFactory, \App\Models\Traits\BelongsToBranch;
    
    protected $fillable = ['name', 'printer_name', 'tenant_id', 'status', 'sort_order', 'print_ticket'];
    //
}
