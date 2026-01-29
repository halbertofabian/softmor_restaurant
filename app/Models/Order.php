<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory, \App\Models\Traits\BelongsToBranch;

    protected $fillable = [
        'table_id', 'user_id', 'status', 'total', 'closed_at', 'notes', 'tenant_id', 'branch_id'
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function calculateTotal()
    {
        $total = $this->details->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });
        $this->update(['total' => $total]);
    }

    public function getGroupedDetailsAttribute()
    {
        return $this->details->groupBy(function ($item) {
            // We load the preparation area relationship or just assume ID if we saved it?
            // OrderDetail saves 'preparation_area_id'.
            // We should eager load preparationArea in OrderDetail if we want the name.
            return $item->preparationArea->name ?? 'Sin Área';
        });
    }
}
