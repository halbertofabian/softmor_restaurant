<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory, \App\Models\Traits\BelongsToTenant;

    protected $fillable = [
        'product_id', 'type', 'quantity',
        'previous_stock', 'new_stock',
        'notes', 'user_id', 'tenant_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
