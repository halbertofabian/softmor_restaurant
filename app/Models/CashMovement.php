<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'cash_register_id',
        'expense_category_id',
        'type',
        'amount',
        'description',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = $model->tenant_id ?? auth()->user()->tenant_id ?? 'default';
                $model->branch_id = $model->branch_id ?? session('branch_id');
                $model->user_id = $model->user_id ?? auth()->id();
            }
        });
    }
}
