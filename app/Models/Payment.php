<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BelongsToBranch;

class Payment extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'order_id',
        'cash_register_id',
        'amount',
        'method',
        'reference'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }
}
