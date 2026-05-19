<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductComboItem extends Model
{
    use HasFactory, \App\Models\Traits\BelongsToBranch;

    protected $fillable = [
        'combo_product_id',
        'component_product_id',
        'default_flavor_id',
        'quantity',
        'sort_order',
        'tenant_id',
        'branch_id',
    ];

    public function comboProduct()
    {
        return $this->belongsTo(Product::class, 'combo_product_id');
    }

    public function componentProduct()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function defaultFlavor()
    {
        return $this->belongsTo(ProductFlavor::class, 'default_flavor_id');
    }
}
