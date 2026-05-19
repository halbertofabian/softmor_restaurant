<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Assuming HasFactory is needed and not imported yet

class Product extends Model
{
    use HasFactory, \App\Models\Traits\BelongsToBranch;

    protected $fillable = [
        'name',
        'description',
        'type',
        'price',
        'category_id',
        'status',
        'image',
        'stock',
        'min_stock',
        'alert_stock',
        'preparation_area_id',
        'controls_inventory',
        'tenant_id',
        'branch_id',
    ];

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

    public function flavors()
    {
        return $this->hasMany(ProductFlavor::class)->orderBy('sort_order')->orderBy('name');
    }

    public function comboItems()
    {
        return $this->hasMany(ProductComboItem::class, 'combo_product_id')
            ->orderBy('sort_order')
            ->with(['componentProduct.flavors', 'defaultFlavor']);
    }
}
