<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFlavor extends Model
{
    use HasFactory, \App\Models\Traits\BelongsToBranch;

    protected $fillable = [
        'product_id',
        'name',
        'additional_price',
        'is_active',
        'sort_order',
        'tenant_id',
        'branch_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'additional_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
