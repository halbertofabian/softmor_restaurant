<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Assuming HasFactory is needed and not imported yet

class Product extends Model
{
    use HasFactory, \App\Models\Traits\BelongsToBranch;

    protected $fillable = ['name', 'description', 'price', 'category_id', 'status', 'image', 'stock', 'alert_stock', 'preparation_area_id', 'controls_inventory', 'tenant_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function preparationArea()
    {
        return $this->belongsTo(PreparationArea::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
