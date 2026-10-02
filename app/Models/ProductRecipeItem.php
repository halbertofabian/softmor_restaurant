<?php

namespace App\Models;

use App\Models\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class ProductRecipeItem extends Model
{
    use BelongsToBranch;

    protected $fillable = ['product_id', 'inventory_item_id', 'quantity', 'tenant_id', 'branch_id'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
