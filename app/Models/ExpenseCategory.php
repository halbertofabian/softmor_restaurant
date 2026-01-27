<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use \App\Models\Traits\BelongsToBranch;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'description',
    ];

    public function movements()
    {
        return $this->hasMany(CashMovement::class);
    }
}
