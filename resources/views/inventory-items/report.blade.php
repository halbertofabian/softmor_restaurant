@extends('layouts.master')
@section('title', 'Reporte de materias primas')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-1">Reporte de materias primas</h4>
        <span class="text-muted">{{ $branch?->name ?? 'Sucursal' }} · Generado el {{ now()->format('d/m/Y H:i') }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory-items.index') }}" class="btn btn-label-secondary"><i class="ti tabler-arrow-left me-1"></i>Volver</a>
        <a href="{{ route('inventory-items.report.pdf') }}" class="btn btn-primary"><i class="ti tabler-file-type-pdf me-1"></i>Guardar en PDF</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><span class="text-muted">Total registradas</span><div class="fs-3 fw-bold mt-1">{{ $summary['total'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><span class="text-muted">Activas</span><div class="fs-3 fw-bold text-success mt-1">{{ $summary['active'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><span class="text-muted">Inactivas</span><div class="fs-3 fw-bold text-secondary mt-1">{{ $summary['inactive'] }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body"><span class="text-muted">Bajo mínimo</span><div class="fs-3 fw-bold {{ $summary['low_stock'] > 0 ? 'text-danger' : 'text-success' }} mt-1">{{ $summary['low_stock'] }}</div></div></div></div>
</div>

<div class="card">
    <div class="card-header border-bottom bg-label-primary"><h5 class="mb-0 text-primary"><i class="ti tabler-package me-2"></i>Existencias actuales</h5></div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Materia prima</th><th>Existencia</th><th>Equivalencia</th><th>Mínimo</th><th class="text-center">Recetas</th><th>Estado</th><th>Último movimiento</th></tr></thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $isLow = $item->min_stock !== null && (float) $item->stock <= (float) $item->min_stock;
                        $equivalent = match($item->base_unit) {
                            'g' => number_format((float) $item->stock / 1000, 3) . ' kg',
                            'ml' => number_format((float) $item->stock / 1000, 3) . ' l',
                            default => '-',
                        };
                    @endphp
                    <tr>
                        <td class="fw-medium">{{ $item->name }}</td>
                        <td class="{{ $isLow ? 'text-danger fw-bold' : '' }}">{{ number_format((float) $item->stock, 3) }} {{ $item->base_unit }}</td>
                        <td>{{ $equivalent }}</td>
                        <td>{{ $item->min_stock === null ? '-' : number_format((float) $item->min_stock, 3) . ' ' . $item->base_unit }}</td>
                        <td class="text-center">{{ $item->recipe_items_count }}</td>
                        <td><span class="badge bg-label-{{ $item->status ? 'success' : 'secondary' }}">{{ $item->status ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>{{ $item->movements_max_created_at ? \Carbon\Carbon::parse($item->movements_max_created_at)->format('d/m/Y H:i') : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay materias primas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
