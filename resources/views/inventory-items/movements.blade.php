@extends('layouts.master')
@section('title', 'Movimientos de inventario')
@section('content')
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="mb-1">Movimientos de {{ $inventoryItem->name }}</h5>
            <span class="text-muted">Historial de entradas y salidas de la materia prima</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory-items.edit', $inventoryItem) }}" class="btn btn-label-primary"><i class="ti tabler-edit me-1"></i>Editar</a>
            <a href="{{ route('inventory-items.index') }}" class="btn btn-label-secondary"><i class="ti tabler-arrow-left me-1"></i>Volver</a>
        </div>
    </div>
    <div class="card-body">
        <div class="card border shadow-none mb-0">
            <div class="card-header border-bottom py-3 bg-label-primary"><h6 class="mb-0 text-primary"><i class="ti tabler-package me-2"></i>Existencia actual</h6></div>
            <div class="card-body pt-3">
                <div class="d-flex flex-wrap align-items-baseline gap-2">
                    <span class="fs-4 fw-bold">{{ number_format((float) $inventoryItem->stock, 3) }} {{ $inventoryItem->base_unit }}</span>
                    @if($inventoryItem->base_unit === 'g')
                        <span class="text-muted">= {{ number_format((float) $inventoryItem->stock / 1000, 3) }} kg</span>
                    @elseif($inventoryItem->base_unit === 'ml')
                        <span class="text-muted">= {{ number_format((float) $inventoryItem->stock / 1000, 3) }} l</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom"><h5 class="mb-0">Historial</h5></div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Cantidad</th><th>Existencia anterior</th><th>Nueva existencia</th><th>Nota</th></tr></thead>
            <tbody>
                @forelse($movements as $movement)
                    @php
                        $movementType = match($movement->type) {
                            'initial' => 'Inventario inicial',
                            'consumption' => 'Consumo',
                            'return' => 'Devolución',
                            'adjustment' => 'Ajuste',
                            'receipt' => 'Entrada',
                            'waste' => 'Merma',
                            default => ucfirst($movement->type),
                        };
                        $movementBadge = match($movement->type) {
                            'consumption', 'waste' => 'danger',
                            'return', 'receipt', 'initial' => 'success',
                            default => 'warning',
                        };
                    @endphp
                    <tr>
                        <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge bg-label-{{ $movementBadge }}">{{ $movementType }}</span></td>
                        <td class="fw-medium {{ (float) $movement->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ (float) $movement->quantity > 0 ? '+' : '' }}{{ number_format((float) $movement->quantity, 3) }} {{ $inventoryItem->base_unit }}</td>
                        <td>{{ number_format((float) $movement->previous_stock, 3) }} {{ $inventoryItem->base_unit }}</td>
                        <td>{{ number_format((float) $movement->new_stock, 3) }} {{ $inventoryItem->base_unit }}</td>
                        <td>{{ $movement->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay movimientos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())<div class="card-footer">{{ $movements->links() }}</div>@endif
</div>
@endsection
