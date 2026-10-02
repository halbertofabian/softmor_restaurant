<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderDetail extends Model
{
    use HasFactory, \App\Models\Traits\BelongsToBranch;

    protected $fillable = [
        'order_id', 'parent_order_detail_id', 'product_id', 'product_flavor_id', 'product_name', 'flavor_name', 'price', 'flavor_price_delta',
        'quantity', 'preparation_area_id', 'notes', 'status', 'is_combo_component', 'tenant_id', 'branch_id', 'is_printed'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function flavor()
    {
        return $this->belongsTo(ProductFlavor::class, 'product_flavor_id');
    }

    public function preparationArea()
    {
        return $this->belongsTo(PreparationArea::class);
    }

    public function parentDetail()
    {
        return $this->belongsTo(self::class, 'parent_order_detail_id');
    }

    public function componentDetails()
    {
        return $this->hasMany(self::class, 'parent_order_detail_id');
    }

    public function ingredientMovements()
    {
        return $this->hasMany(InventoryItemMovement::class);
    }
}
