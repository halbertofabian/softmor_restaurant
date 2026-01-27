<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use \App\Models\Traits\BelongsToBranch;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'user_id',
        'opening_amount',
        'closing_amount',
        'calculated_amount',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'calculated_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movements()
    {
        return $this->hasMany(CashMovement::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
