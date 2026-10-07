<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BelongsToBranch;

class Payment extends Model
{
    use HasFactory, BelongsToBranch;

    public const METHOD_LABELS = [
        'cash' => 'Efectivo',
        'card' => 'Tarjeta',
        'transfer' => 'Transferencia',
        'mixed' => 'Mixto',
    ];

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'order_id',
        'cash_register_id',
        'amount',
        'method',
        'reference'
    ];

    public static function methodLabel(?string $method): string
    {
        return self::METHOD_LABELS[$method] ?? ($method ? ucfirst($method) : '');
    }

    public function getMethodLabelAttribute(): string
    {
        return self::methodLabel($this->method);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }
}
