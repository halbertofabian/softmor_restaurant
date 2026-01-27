<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderDetail extends Model
{
    use HasFactory, \App\Models\Traits\BelongsToBranch;

    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'price',
        'quantity', 'preparation_area_id', 'notes', 'status', 'tenant_id'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function preparationArea()
    {
        return $this->belongsTo(PreparationArea::class);
    }
}
