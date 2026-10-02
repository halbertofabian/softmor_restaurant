<?php

namespace App\Models;

use App\Models\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class InventoryItemMovement extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'inventory_item_id', 'order_detail_id', 'reversed_movement_id', 'type', 'quantity',
        'previous_stock', 'new_stock', 'notes', 'user_id', 'tenant_id', 'branch_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'previous_stock' => 'decimal:3',
        'new_stock' => 'decimal:3',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }
}
