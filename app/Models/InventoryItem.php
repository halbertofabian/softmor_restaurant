<?php

namespace App\Models;

use App\Models\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use BelongsToBranch;

    protected $fillable = ['name', 'base_unit', 'stock', 'min_stock', 'status', 'tenant_id', 'branch_id'];

    protected $casts = [
        'stock' => 'decimal:3',
        'min_stock' => 'decimal:3',
        'status' => 'boolean',
    ];

    public function recipeItems()
    {
        return $this->hasMany(ProductRecipeItem::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryItemMovement::class);
    }
}
