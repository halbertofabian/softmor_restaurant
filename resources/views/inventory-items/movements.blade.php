@extends('layouts.master')
@section('title', 'Inventario de materias primas')
@section('content')
@php
    $unitOptions = match($inventoryItem->base_unit) {
        'g' => ['g', 'kg'],
        'ml' => ['ml', 'l'],
        default => ['unit'],
    };
@endphp
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="mb-1">Inventario de {{ $inventoryItem->name }}</h5>
            <span class="text-muted">Entradas, salidas y ajustes de existencia</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustmentModal"><i class="ti tabler-adjustments me-1"></i>Ajustar existencia</button>
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
                            'waste' => 'Salida',
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
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())<div class="card-footer">{{ $movements->links() }}</div>@endif
</div>

<div class="modal fade" id="adjustmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('inventory-items.movements.store', $inventoryItem) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti tabler-adjustments me-1"></i>Ajustar existencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="type" id="movement_type" required>
                            <option value="receipt" @selected(old('type') === 'receipt')>Entrada</option>
                            <option value="waste" @selected(old('type') === 'waste')>Salida</option>
                            <option value="adjustment" @selected(old('type') === 'adjustment')>Ajuste</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="movement_quantity_label">Cantidad</label>
                        <div class="input-group">
                            <input type="number" step="0.001" min="0" class="form-control" name="quantity" value="{{ old('quantity') }}" required>
                            <select class="form-select" name="quantity_unit" required style="max-width: 90px;">
                                @foreach($unitOptions as $unit)
                                    <option value="{{ $unit }}" @selected(old('quantity_unit', $inventoryItem->base_unit) === $unit)>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text mt-2" id="movement_hint">Suma existencia al inventario.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Nota</label>
                        <textarea class="form-control" name="notes" rows="3" maxlength="255" placeholder="Opcional">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('movement_type').addEventListener('change', function () {
    const label = document.getElementById('movement_quantity_label');
    const hint = document.getElementById('movement_hint');

    if (this.value === 'adjustment') {
        label.textContent = 'Nueva existencia';
        hint.textContent = 'Establece la existencia real contada; se registra la diferencia.';
    } else if (this.value === 'waste') {
        label.textContent = 'Cantidad';
        hint.textContent = 'Resta existencia al inventario (merma o salida).';
    } else {
        label.textContent = 'Cantidad';
        hint.textContent = 'Suma existencia al inventario.';
    }
});
@if($errors->any())
new bootstrap.Modal(document.getElementById('adjustmentModal')).show();
@endif
</script>
@endpush
@endsection
