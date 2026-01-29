<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Table extends Model
{
    use \App\Models\Traits\BelongsToBranch, SoftDeletes;

    protected $fillable = ['name', 'capacity', 'zone', 'status', 'is_active', 'tenant_id'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrder()
    {
        return $this->hasOne(Order::class)->where('status', 'open')->latest();
    }
}
